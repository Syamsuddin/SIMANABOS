<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Akun admin pertama di produksi (seeder tidak dijalankan, docs/25). Sandi hanya lewat prompt tersembunyi
 * agar tidak tersimpan di riwayat shell; akun wajib ganti sandi saat login pertama.
 */
#[AsCommand(name: 'simanabos:create-admin', description: 'Buat akun admin dinas (wajib ganti sandi saat login pertama)')]
class CreateAdminCommand extends Command
{
    protected $signature = 'simanabos:create-admin {--username= : Nama pengguna} {--name= : Nama lengkap}';

    public function handle(): int
    {
        $data = [
            'username' => $this->option('username') ?: $this->ask('Nama pengguna'),
            'name' => $this->option('name') ?: $this->ask('Nama lengkap'),
            'password' => $this->secret('Kata sandi sementara (min. 10 karakter, huruf dan angka)'),
        ];
        $data['password_confirmation'] = $this->secret('Ulangi kata sandi');

        $validator = Validator::make($data, [
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'name' => ['required', 'string', 'max:150'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'username' => $data['username'],
                'name' => $data['name'],
                'password' => $data['password'],
                'must_change_password' => true,
                'is_active' => true,
            ]);
            $user->assignRole(RoleName::AdminDinas->value);

            return $user;
        });

        $this->info("Admin dinas '{$user->username}' dibuat. Sandi wajib diganti saat login pertama.");

        return self::SUCCESS;
    }
}
