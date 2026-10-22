<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    })
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/**
 * Buat pengguna ber-role; atribut factory bisa ditimpa.
 *
 * @param  array<string, mixed>  $attributes
 */
function userWithRole(RoleName $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role->value);

    return $user;
}
