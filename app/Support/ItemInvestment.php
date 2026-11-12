<?php
namespace App\Support;
use App\Models\Item;
class ItemInvestment {
    public static function summary(Item $item): array {
        $totals=$item->entries()->whereNull('voided_at')->selectRaw('kind, SUM(amount) as amount, SUM(uses) as uses, SUM(days) as days, MAX(occurred_on) as last_date')->groupBy('kind')->get()->keyBy('kind');
        $amount=fn($kind)=>(float)($totals->get($kind)?->amount??0);
        $acquisition=(float)$item->acquisition_total; $storage=$amount('storage');$repairs=$amount('repair');$other=$amount('other_cost');$revenue=$amount('revenue');
        $invested=$acquisition+$storage+$repairs+$other;$uses=(int)($totals->get('usage')?->uses??0);
        $advice='Build the history';$reason='Log usage, revenue and costs, then add estimates to compare your next move.';
        $repair=$item->repair_estimate;$reuse=$item->reuse_revenue_estimate;$resale=$item->resale_estimate;
        if(in_array($item->status,['sold','retired'])) {$advice=ucfirst($item->status);$reason='This item is out of circulation. Its history remains available.';}
        elseif($item->condition==='damaged') {
            $advice='Review repair';$reason='Add a repair quote and expected revenue from the next use before committing.';
            if($repair!==null && $reuse!==null) {
                if((float)$reuse>(float)$repair) {$advice='Consider repair';$reason='Expected next-use revenue exceeds the repair estimate by USD $'.number_format((float)$reuse-(float)$repair,2).', before other future costs.';}
                elseif($resale!==null && (float)$resale>0) {$advice='Consider selling';$reason='The repair estimate meets or exceeds expected next-use revenue. Compare the as-is resale estimate before deciding.';}
                elseif($resale!==null && (float)$resale===0.0 && (float)$reuse===0.0) {$advice='Review retirement';$reason='Your estimates show no next-use revenue or resale value. Check donation, disposal and any remaining obligations.';}
            }
        } elseif($reuse!==null && $resale!==null) {
            $advice=(float)$reuse>=(float)$resale?'Consider reuse':'Compare selling';
            $reason='Next-use revenue: USD $'.number_format((float)$reuse,2).'. As-is resale: USD $'.number_format((float)$resale,2).'. Future uses and unrecorded costs are not included.';
        } elseif($uses>0) {$advice='Ready to reuse';$reason='Review condition and availability, then compare your next-use and resale estimates.';}
        return compact('acquisition','storage','repairs','other','revenue','invested','uses','advice','reason')+['net'=>$revenue-$invested,'cost_per_use'=>$uses?$invested/$uses:null,'days'=>(int)($totals->get('usage')?->days??0),'last_used'=>$totals->get('usage')?->last_date];
    }
}
