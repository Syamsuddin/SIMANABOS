<?php

namespace App\Actions\Schools;

use App\Models\School;
use App\Models\SchoolOfficial;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Mencatat usulan pergantian pengelola. Usulan admin langsung disahkan;
 * usulan operator menunggu pengesahan admin (docs/05).
 */
class ProposeOfficial
{
    public function __construct(private readonly ApproveOfficial $approve) {}

    /**
     * @param  array{role: string, name: string, nip?: ?string, started_at: string}  $data
     */
    public function __invoke(School $school, array $data, User $proposer): SchoolOfficial
    {
        $pendingExists = SchoolOfficial::query()
            ->pending()
            ->where('school_id', $school->id)
            ->where('role', $data['role'])
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages(['role' => 'Peran ini sudah punya usulan yang menunggu pengesahan.']);
        }

        $official = SchoolOfficial::query()->create([
            'school_id' => $school->id,
            'role' => $data['role'],
            'name' => $data['name'],
            'nip' => $data['nip'] ?? null,
            'started_at' => $data['started_at'],
        ]);

        return $proposer->can('approve', $official) ? ($this->approve)($official, $proposer) : $official;
    }
}
