<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class AppSetting extends Model
{
    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row === null ? $default : $row->value;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Persetujuan kepala sekolah opsional; default nonaktif (docs/07 Sistem). */
    public static function requiresPrincipalApproval(): bool
    {
        return (bool) static::get('require_principal_approval', false);
    }

    /** Tanggal tenggat bawaan saat tahun anggaran baru dibuka (docs/06 §A). */
    public static function defaultDeadlineDay(): int
    {
        return (int) static::get('default_deadline_day', 10);
    }

    /** Jumlah hari sebelum tenggat saat pengingat dikirim (docs/06 §H). */
    public static function reminderDaysBefore(): int
    {
        return (int) static::get('reminder_days_before', 3);
    }
}
