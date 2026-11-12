<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
class ProfileImageController {
    public function __invoke(int $user) {
        abort_unless(auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')),403);
        $record=User::visibleTo(auth()->user())->findOrFail($user);
        $path=$record->profile_image;
        $disk=Storage::disk('local');
        abort_unless($path && $disk->exists($path),404);
        abort_unless(in_array($disk->mimeType($path),['image/jpeg','image/png','image/webp'],true),404);
        return response()->file($disk->path($path),['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
