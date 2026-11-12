<?php
namespace App\Services;

use App\Models\{Item, ItemActivity, ItemEntry, ItemHandoff, User, Warehouse};
use Illuminate\Support\Facades\{DB, Gate, Storage, Validator};
use Illuminate\Validation\{Rule, ValidationException};

class ItemOperations
{
    public const STATUSES = ['available'=>'Available','reserved'=>'Reserved','installed'=>'Installed','repair'=>'In repair','sold'=>'Sold','retired'=>'Retired'];
    public const ROLES = ['designer'=>'Designer','receiving'=>'Receiving warehouse','repair'=>'Repair vendor','installer'=>'Installer'];

    public function internal(User $actor, Item $item): bool
    {
        return $actor->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')) && Gate::forUser($actor)->allows('view', $item);
    }

    public function canView(User $actor, Item $item): bool
    {
        return $this->internal($actor,$item) || ($actor->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'))
            && $item->warehouse->owner->is_active && $item->shares()->active()->where('user_id',$actor->id)->exists());
    }

    public function canContribute(User $actor, Item $item): bool
    {
        return $this->canView($actor,$item) && (Gate::forUser($actor)->allows('update',$item)
            || $item->shares()->active()->where('user_id',$actor->id)->where('access','contribute')->exists());
    }

    public function canShare(User $actor, Item $item): bool
    {
        return $this->internal($actor,$item) && ($actor->isSuper() || $item->warehouse->owner_id === $actor->id);
    }

    public function record(User $actor, int $id): Item
    {
        $item=Item::with(['warehouse.owner','inventoryGroup.project','responsible'])->findOrFail($id);
        if (!$this->canView($actor,$item)) throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Item::class,[$id]);
        return $item;
    }

    public function quickUpdate(User $actor, int $id, int $revision, array $changes): Item
    {
        return DB::transaction(function() use($actor,$id,$revision,$changes) {
            $item=Item::lockForUpdate()->findOrFail($id);
            Gate::forUser($actor)->authorize('update',$item);
            abort_unless($this->internal($actor,$item),403);
            if ((int)$item->revision !== $revision) throw ValidationException::withMessages(['record'=>'This item changed while you were working. Close this panel and open it again.']);
            $allowed=array_intersect_key($changes,array_flip(['status','condition','quantity','warehouse_id','inventory_group_id','location']));
            $data=array_merge($item->only(['name','sku','barcode','category','location','condition','status','quantity','low_stock_threshold','unit_cost','unit_price','warehouse_id','inventory_group_id']),$allowed);
            return app(InventoryWriter::class)->save($actor,Item::class,$data,$item);
        });
    }

    public function grant(User $actor, Item $item, array $data): void
    {
        abort_unless($this->canShare($actor,$item),403);
        $valid=Validator::make($data,['email'=>'required|email|max:255','organization'=>'required|string|max:120','role'=>['required',Rule::in(array_keys(self::ROLES))],'access'=>['required',Rule::in(['view','contribute'])],'expires_at'=>'nullable|date|after:today'])->validate();
        $user=User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($valid['email']))])->first();
        if (!$user || !$user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'))) throw ValidationException::withMessages(['email'=>'Use the email of an active Luxe Vault account. Ask your collaborator to register first.']);
        if ($this->internal($user,$item)) throw ValidationException::withMessages(['email'=>'This person already has access through your warehouse.']);
        DB::transaction(function() use($actor,$item,$valid,$user) {
            $item->shares()->updateOrCreate(['user_id'=>$user->id],['granted_by'=>$actor->id,'organization'=>$valid['organization'],'role'=>$valid['role'],'access'=>$valid['access'],'expires_at'=>$valid['expires_at']??null,'revoked_at'=>null]);
            $this->activity($actor,$item,'access','Access granted to '.$user->name.' · '.$valid['organization'].' ('.$valid['access'].').',[], 'internal');
        });
    }

    public function revoke(User $actor, Item $item, int $shareId): void
    {
        abort_unless($this->canShare($actor,$item),403);
        DB::transaction(function() use($actor,$item,$shareId) {
            $locked=Item::lockForUpdate()->findOrFail($item->id);
            $share=$locked->shares()->findOrFail($shareId);
            $share->update(['revoked_at'=>now()]);
            $locked->handoffs()->where('status','pending')->where('to_user_id',$share->user_id)->update(['status'=>'cancelled','responded_at'=>now(),'response'=>'Access revoked.']);
            if($locked->responsible_user_id===$share->user_id) {$locked->responsible_user_id=null;$locked->save();}
            $this->activity($actor,$locked,'access','Access revoked for '.$share->user->name.'.',[],'internal');
        });
    }

    public function activity(User $actor, Item $item, string $kind, string $body, array $attachments=[], string $visibility='shared'): ItemActivity
    {
        return $item->activities()->create(['user_id'=>$actor->id,'kind'=>$kind,'body'=>$body,'attachments'=>$attachments,'visibility'=>$visibility]);
    }

    public function addNote(User $actor, Item $item, array $data): void
    {
        abort_unless($this->canContribute($actor,$item),403);
        $valid=Validator::make($data,['body'=>'required|string|max:5000','attachments'=>'nullable|array|max:4','attachments.*'=>'required|string|max:500'])->validate();
        $disk=Storage::disk('local');
        foreach($valid['attachments']??[] as $path) {
            if(!preg_match('/^item-evidence\/'.$actor->id.'\/[A-Za-z0-9]+\.(jpg|jpeg|png|webp|pdf)$/i',$path) || !$disk->exists($path)
                || !in_array($disk->mimeType($path),['image/jpeg','image/png','image/webp','application/pdf'],true) || $disk->size($path)>5*1024*1024) {
                throw ValidationException::withMessages(['attachments'=>'Upload a JPG, PNG, WebP or PDF up to 5 MB.']);
            }
        }
        $this->activity($actor,$item,'note',$valid['body'],$valid['attachments']??[]);
    }

    public function addEntry(User $actor, Item $item, array $data): void
    {
        abort_unless($this->internal($actor,$item),403); Gate::forUser($actor)->authorize('update',$item);
        $valid=Validator::make($data,['kind'=>['required',Rule::in(['storage','repair','other_cost','revenue','usage'])],'occurred_on'=>'required|date|before_or_equal:today','amount'=>'nullable|numeric|min:0|max:9999999999.99','uses'=>'nullable|integer|min:1|max:1000000','days'=>'nullable|integer|min:0|max:1000000','notes'=>'nullable|string|max:2000','request_key'=>'required|uuid'])->validate();
        if($valid['kind']!=='usage' && (float)($valid['amount']??0)<=0) throw ValidationException::withMessages(['amount'=>'Enter an amount greater than zero.']);
        $valid['amount']=$valid['kind']==='usage'?0:$valid['amount'];
        $valid['uses']=$valid['kind']==='usage'?($valid['uses']??1):0;
        $valid['days']=$valid['kind']==='usage'?($valid['days']??0):0;
        DB::transaction(function() use($actor,$item,$valid) {
            Item::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if(ItemEntry::where('request_key',$valid['request_key'])->exists()) return;
            $item->entries()->create($valid+['user_id'=>$actor->id]);
            $this->activity($actor,$item,'investment',ucfirst(str_replace('_',' ',$valid['kind'])).' recorded for '.$valid['occurred_on'].'.',[],'internal');
        });
    }

    public function voidEntry(User $actor, Item $item, int $entryId, string $reason): void
    {
        abort_unless($this->internal($actor,$item),403); Gate::forUser($actor)->authorize('update',$item);
        Validator::make(['reason'=>$reason],['reason'=>'required|string|max:255'])->validate();
        $entry=$item->entries()->whereNull('voided_at')->findOrFail($entryId);
        $entry->update(['voided_at'=>now(),'voided_by'=>$actor->id,'void_reason'=>$reason]);
        $this->activity($actor,$item,'investment','Entry corrected: '.$reason,[],'internal');
    }

    public function saveInvestment(User $actor, Item $item, array $data): void
    {
        abort_unless($this->internal($actor,$item),403); Gate::forUser($actor)->authorize('update',$item);
        $valid=Validator::make($data,['acquisition_total'=>'required|numeric|min:0|max:99999999999999.99','acquired_on'=>'nullable|date|before_or_equal:today','repair_estimate'=>'nullable|numeric|min:0|max:9999999999.99','reuse_revenue_estimate'=>'nullable|numeric|min:0|max:9999999999.99','resale_estimate'=>'nullable|numeric|min:0|max:9999999999.99'])->validate();
        DB::transaction(function() use($actor,$item,$valid) {
            $item->forceFill($valid)->save();
            $this->activity($actor,$item,'investment','Acquisition details and decision estimates updated.',[],'internal');
        });
    }

    public function decide(User $actor, Item $item, array $data): void
    {
        abort_unless($this->internal($actor,$item),403); Gate::forUser($actor)->authorize('update',$item);
        $valid=Validator::make($data,['decision'=>['required',Rule::in(['reuse','repair','sell','retire'])],'reason'=>'required|string|max:2000'])->validate();
        $this->activity($actor,$item,'decision',ucfirst($valid['decision']).' — '.$valid['reason']);
    }

    public function participants(Item $item): \Illuminate\Support\Collection
    {
        $users=collect([$item->warehouse->owner])->merge($item->warehouse->staff)->merge($item->shares()->active()->with('user')->get()->pluck('user'));
        return $users->unique('id')->filter(fn($u)=>$u && $this->canContribute($u,$item))->keyBy('id');
    }

    public function requestHandoff(User $actor, Item $item, array $data): void
    {
        abort_unless($this->canContribute($actor,$item),403);
        abort_unless($this->internal($actor,$item) || $item->responsible_user_id===$actor->id,403);
        $valid=Validator::make($data,['to_user_id'=>'required|integer','due_on'=>'nullable|date|after_or_equal:today','instructions'=>'required|string|max:2000'])->validate();
        $recipient=$this->participants($item)->get($valid['to_user_id']);
        if(!$recipient || $recipient->id===$actor->id) throw ValidationException::withMessages(['to_user_id'=>'Choose another person with current access to this record.']);
        DB::transaction(function() use($actor,$item,$valid,$recipient) {
            Item::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if($item->handoffs()->where('status','pending')->exists()) throw ValidationException::withMessages(['to_user_id'=>'Resolve the pending handoff first.']);
            $item->handoffs()->create($valid+['from_user_id'=>$actor->id]);
            $this->activity($actor,$item,'handoff','Handoff requested to '.$recipient->name.'. '.$valid['instructions']);
        });
    }

    public function respondHandoff(User $actor, Item $item, int $id, string $response, ?string $note=null): void
    {
        abort_unless($this->canContribute($actor,$item),403);
        Validator::make(['response'=>$response,'note'=>$note],['response'=>['required',Rule::in(['accepted','declined','cancelled'])],'note'=>'nullable|string|max:2000'])->validate();
        DB::transaction(function() use($actor,$item,$id,$response,$note) {
            $locked=Item::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $handoff=$locked->handoffs()->where('status','pending')->findOrFail($id);
            abort_unless($response==='cancelled' ? ($handoff->from_user_id===$actor->id || $this->canShare($actor,$locked)) : $handoff->to_user_id===$actor->id,403);
            $handoff->update(['status'=>$response,'response'=>$note,'responded_at'=>now()]);
            if($response==='accepted') {$locked->responsible_user_id=$actor->id;$locked->save();}
            $this->activity($actor,$locked,'handoff','Handoff '.$response.'.'.($note?' '.$note:''));
        });
    }
}
