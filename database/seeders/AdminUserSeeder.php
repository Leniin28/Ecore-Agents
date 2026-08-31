<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('El administrador demo solo se crea en el entorno local.');

            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@ecore.local'],
            [
                'name' => 'Administrador Demo',
                'password' => 'ECoreDemo2026!',
            ],
        );

        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }
}
