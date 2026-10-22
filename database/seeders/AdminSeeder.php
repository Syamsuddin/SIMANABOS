<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Akun contoh untuk pengembangan lokal. Produksi memakai `simanabos:create-admin`.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $admin = User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin Dinas Contoh',
                'password' => 'Sandi12345',
                'must_change_password' => true,
                'is_active' => true,
            ],
        );

        $admin->syncRoles([RoleName::AdminDinas->value]);
    }
}
