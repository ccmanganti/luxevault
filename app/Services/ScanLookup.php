<?php
namespace App\Services;
use App\Models\{Item, User};
use Illuminate\Validation\ValidationException;
class ScanLookup {
    public function find(User $actor, string $code): \Illuminate\Database\Eloquent\Collection {
        $code=trim($code);
        if(mb_strlen($code)>120) throw ValidationException::withMessages(['lookupCode'=>'This code is too long. Scan the item label or enter its SKU.']);
        if($code==='') return new \Illuminate\Database\Eloquent\Collection;
        // UPC-A and zero-prefixed EAN-13 identify the same product. Preserve every other leading zero.
        $codes=[$code];
        if(preg_match('/^\d{12}$/',$code)) $codes[]='0'.$code;
        if(preg_match('/^0\d{12}$/',$code)) $codes[]=substr($code,1);
        return Item::visibleTo($actor)->where(fn($q)=>$q->whereIn('barcode',$codes)->orWhere('sku',$code)->orWhere('qr_code',$code))
            ->with(['warehouse','inventoryGroup.project'])->orderBy('name')->limit(21)->get();
    }
}
