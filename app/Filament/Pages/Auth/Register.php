<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Register extends \Filament\Auth\Pages\Register
{
    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = User::create(Arr::only($data, ['name', 'email', 'password']));
            $user->assignRole('warehouse_owner');

            return $user;
        });
    }

    public function getHeading(): string
    {
        return 'Your first warehouse is free.';
    }

    public function getSubheading(): string
    {
        return 'Create your Luxe Vault account. No card required.';
    }
}
