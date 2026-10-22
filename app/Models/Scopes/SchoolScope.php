<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Membatasi role sekolah ke `school_id` miliknya (docs/05 §Aturan penegakan).
 * Objek sekolah lain tidak ditemukan sehingga route binding menghasilkan 404.
 */
class SchoolScope implements Scope
{
    public function __construct(private readonly string $column = 'school_id') {}

    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::hasUser()) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->isSchoolUser()) {
            $builder->where($model->qualifyColumn($this->column), $user->school_id);
        }
    }
}
