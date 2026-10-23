<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->dir = storage_path('framework/testing/backups-'.uniqid());
    config(['simanabos.backup.path' => $this->dir, 'simanabos.backup.retention_days' => 30]);
});

afterEach(function () {
    File::deleteDirectory($this->dir);
});

/** mysqldump palsu: menulis berkas hasil seperti aslinya; tar tetap berjalan sungguhan. */
function fakeMysqldump(bool $ok = true): void
{
    Process::fake([
        '*mysqldump*' => function (PendingProcess $process) use ($ok) {
            if ($ok) {
                preg_match("/--result-file=([^' ]+)/", implode(' ', (array) $process->command), $m);
                file_put_contents($m[1], "-- dump\nCREATE TABLE x (id int);\n");
            }

            return Process::result(output: '', errorOutput: $ok ? '' : 'Access denied', exitCode: $ok ? 0 : 2);
        },
    ]);
}

it('membuat dump basis data dan arsip PDF berizin terbatas', function () {
    fakeMysqldump();

    $this->artisan('simanabos:backup')->assertSuccessful();

    $dump = File::glob($this->dir.'/db-*.sql');
    $archive = File::glob($this->dir.'/pdf-*.tar.gz');
    expect($dump)->toHaveCount(1)->and($archive)->toHaveCount(1)
        ->and(filesize($archive[0]))->toBeGreaterThan(0)
        ->and(substr(sprintf('%o', fileperms($dump[0])), -4))->toBe('0640');

    Process::assertRan(fn (PendingProcess $p) => str_contains(implode(' ', (array) $p->command), 'mysqldump')
        && ! str_contains(implode(' ', (array) $p->command), '--password')
        && array_key_exists('MYSQL_PWD', $p->environment));
});

it('gagal dan tidak memangkas bila dump gagal', function () {
    fakeMysqldump(ok: false);
    File::ensureDirectoryExists($this->dir);
    touch($old = $this->dir.'/db-20200101-000000.sql', now()->subDays(60)->getTimestamp());

    $this->artisan('simanabos:backup')->assertFailed();

    expect(is_file($old))->toBeTrue();
});

it('memangkas hanya cadangan yang melewati masa retensi', function () {
    fakeMysqldump();
    File::ensureDirectoryExists($this->dir);
    touch($old = $this->dir.'/db-20200101-000000.sql', now()->subDays(31)->getTimestamp());
    touch($recent = $this->dir.'/pdf-20200102-000000.tar.gz', now()->subDays(5)->getTimestamp());
    touch($other = $this->dir.'/catatan.txt', now()->subDays(90)->getTimestamp());

    $this->artisan('simanabos:backup')->assertSuccessful();

    expect(is_file($old))->toBeFalse()
        ->and(is_file($recent))->toBeTrue()
        ->and(is_file($other))->toBeTrue();
});

it('dijadwalkan harian', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains($e->command ?? '', 'simanabos:backup'));

    expect($events)->toHaveCount(1)->and($events->first()->expression)->toBe('30 1 * * *');
});
