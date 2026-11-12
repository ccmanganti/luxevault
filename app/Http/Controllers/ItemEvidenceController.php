<?php
namespace App\Http\Controllers;
use App\Models\ItemActivity;
use App\Services\ItemOperations;
use Illuminate\Support\Facades\Storage;
class ItemEvidenceController {
    public function __invoke(int $activity, int $index) {
        $entry=ItemActivity::findOrFail($activity);$ops=app(ItemOperations::class);
        $item=$ops->record(auth()->user(),$entry->item_id);
        abort_unless($entry->visibility==='shared'||$ops->internal(auth()->user(),$item),404);
        $path=$entry->attachments[$index]??null;$disk=Storage::disk('local');
        abort_unless($path && $disk->exists($path),404);
        abort_unless(in_array($disk->mimeType($path),['image/jpeg','image/png','image/webp','application/pdf'],true),404);
        return response()->download($disk->path($path),'item-evidence-'.($index+1).'.'.pathinfo($path,PATHINFO_EXTENSION),['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
