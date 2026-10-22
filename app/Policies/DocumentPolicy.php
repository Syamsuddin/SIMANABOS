<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Document;
use App\Models\MonthlyReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /** Unduh: dinas semua; role sekolah hanya milik sekolahnya (docs/05). Sekolah lain → 404. */
    public function view(User $user, Document $document): Response|bool
    {
        $owner = $document->documentable;
        if ($owner === null) {
            return Response::denyAsNotFound();
        }

        $isDinas = $user->hasAnyRole([RoleName::AdminDinas->value, RoleName::PemeriksaDinas->value, RoleName::PimpinanDinas->value]);
        $isOwnSchool = $user->isSchoolUser() && $owner instanceof MonthlyReport && $owner->school_id === $user->school_id;

        return $isDinas || $isOwnSchool ? true : Response::denyAsNotFound();
    }

    /** Unggah, ganti, tandai: sama dengan hak mengubah laporan (operator, draf/perlu perbaikan, tahun terbuka). */
    public function manage(User $user, Document $document): bool
    {
        $owner = $document->documentable;

        return $owner instanceof MonthlyReport && $user->can('update', $owner);
    }

    /** Hapus hanya sebelum pengajuan pertama (docs/07 documents.deleted_at). */
    public function delete(User $user, Document $document): bool
    {
        return $this->manage($user, $document) && $document->documentable->submitted_at === null;
    }
}
