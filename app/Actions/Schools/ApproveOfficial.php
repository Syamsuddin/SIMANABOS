<?php

namespace App\Actions\Schools;

use App\Models\SchoolOfficial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mengesahkan usulan pengelola: pengelola aktif sebelumnya (peran sama) ditutup
 * sehari sebelum `started_at` usulan, sehingga hanya satu yang aktif (docs/07).
 */
class ApproveOfficial
{
    public function __invoke(SchoolOfficial $official, User $approver): SchoolOfficial
    {
        return DB::transaction(function () use ($official, $approver) {
            $official = SchoolOfficial::query()->lockForUpdate()->findOrFail($official->id);

            if (! $official->isPending()) {
                throw ValidationException::withMessages(['official' => 'Usulan ini sudah disahkan.']);
            }

            $current = SchoolOfficial::query()
                ->active()
                ->where('school_id', $official->school_id)
                ->where('role', $official->role)
                ->lockForUpdate()
                ->first();

            if ($current) {
                if ($official->started_at->lessThanOrEqualTo($current->started_at)) {
                    throw ValidationException::withMessages([
                        'started_at' => 'Tanggal mulai harus setelah tanggal mulai pengelola yang menjabat ('.$current->started_at->format('d-m-Y').').',
                    ]);
                }

                $current->update(['ended_at' => $official->started_at->copy()->subDay()]);
            }

            $official->update(['approved_by' => $approver->id, 'approved_at' => now()]);

            return $official;
        });
    }
}
