<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;

class EnsureActiveAccount
{
    public function handle($request, Closure $next)
    {
        abort_unless($request->user()?->canAccessPanel(Filament::getCurrentPanel()), 403, 'This account is inactive or its owner no longer has guest access.');

        return $next($request);
    }
}
