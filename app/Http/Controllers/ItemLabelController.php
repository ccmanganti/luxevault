<?php

namespace App\Http\Controllers;

use App\Models\Item;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Gate;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ItemLabelController
{
    public function __invoke(Item $item)
    {
        Gate::authorize('view', $item);
        $qr = (new Writer(new ImageRenderer(new RendererStyle(180), new SvgImageBackEnd)))->writeString($item->qr_code);
        $barcode = (new BarcodeGeneratorSVG)->getBarcode($item->sku, BarcodeGeneratorSVG::TYPE_CODE_128, 2, 48);

        return response()->view('items.label', compact('item', 'qr', 'barcode'))->header('Cache-Control', 'private, no-store');
    }
}
