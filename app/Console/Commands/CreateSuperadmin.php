<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateSuperadmin extends Command
{
    protected $signature = 'luxe:superadmin';

    protected $description = 'Create the first platform administrator with an interactively entered password';

    public function handle(): int
    {
        $data = ['name' => $this->ask('Name'), 'email' => $this->ask('Email'), 'password' => $this->secret('Password (at least 12 characters)')];
        $v = Validator::make($data, ['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12']);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

return self::FAILURE;
        }
        DB::transaction(function () use ($data) {
            $user = User::create($data);
            $user->assignRole('superadmin');
        });
        $this->info('Superadmin created. Sign in at /admin/login.');

        return self::SUCCESS;
    }
}
