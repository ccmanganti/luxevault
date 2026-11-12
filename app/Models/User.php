<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, \Filament\Models\Contracts\HasAvatar
{
    use HasFactory, HasRoles, Notifiable;

    protected $attributes = ['plan' => 'free', 'is_active' => true];

    protected $fillable = ['name', 'email', 'password', 'profile_image'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_image && \Illuminate\Support\Facades\Storage::disk('local')->exists($this->profile_image)
            ? route('profile.image', ['user' => $this->id, 'v' => $this->updated_at?->timestamp]) : null;
    }

    public function owner()
    {
        return $this->belongsTo(self::class, 'owner_id');
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_user');
    }

    public function ownedWarehouses()
    {
        return $this->hasMany(Warehouse::class, 'owner_id');
    }

    public function isSuper(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function isOwner(): bool
    {
        return $this->hasRole('warehouse_owner');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && ($this->isSuper() || $this->isOwner() || ($this->hasRole('staff') && $this->owner?->is_active && $this->owner?->plan === 'premium'));
    }

    public function scopeVisibleTo($query, User $actor)
    {
        return $actor->isSuper() ? $query : $query->where(fn ($q) => $q->whereKey($actor->id)->orWhere('owner_id', $actor->id));
    }
}
