<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Cadangan harian: dump basis data + arsip PDF `storage/app/private` (docs/22 Rollback, docs/25 langkah 4).
 * Berkas lebih tua dari masa retensi dipangkas. Sandi basis data dikirim lewat MYSQL_PWD, bukan argumen.
 */
#[AsCommand(name: 'simanabos:backup', description: 'Cadangkan basis data dan arsip PDF, lalu pangkas cadangan lama')]
class BackupCommand extends Command
{
    public function handle(): int
    {
        $dir = config('simanabos.backup.path');
        File::ensureDirectoryExists($dir, 0750);
        $stamp = now('Asia/Makassar')->format('Ymd-His');
        $dumpFile = "{$dir}/db-{$stamp}.sql";
        $archiveFile = "{$dir}/pdf-{$stamp}.tar.gz";
        $db = config('database.connections.'.config('database.default'));

        $dump = Process::env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])->timeout(1800)->run([
            config('simanabos.backup.mysqldump'),
            '--single-transaction', '--quick', '--routines', '--no-tablespaces',
            '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
            '--result-file='.$dumpFile,
            $db['database'],
        ]);

        $privateRoot = storage_path('app');
        $archive = Process::timeout(1800)->run(['tar', '-czf', $archiveFile, '-C', $privateRoot, 'private']);

        $failed = collect([
            'basis data' => [$dump, $dumpFile],
            'arsip PDF' => [$archive, $archiveFile],
        ])->filter(fn ($step) => $step[0]->failed() || ! is_file($step[1]) || filesize($step[1]) === 0);

        if ($failed->isNotEmpty()) {
            foreach ($failed as $label => [$result]) {
                $this->error("Cadangan {$label} gagal: ".trim($result->errorOutput()));
            }
            Log::error('Cadangan gagal', ['steps' => $failed->keys()->all()]);

            return self::FAILURE;
        }

        // Dump memuat hash sandi dan data sekolah: tidak boleh terbaca pengguna lain di server.
        chmod($dumpFile, 0640);
        chmod($archiveFile, 0640);
        $pruned = $this->prune($dir, (int) config('simanabos.backup.retention_days'));

        $this->info("Cadangan basis data: {$dumpFile} (".filesize($dumpFile).' bita)');
        $this->info("Cadangan arsip PDF: {$archiveFile} (".filesize($archiveFile).' bita)');
        $this->info("Cadangan lama dipangkas: {$pruned}");
        Log::info('Cadangan selesai', ['db' => $dumpFile, 'pdf' => $archiveFile, 'pruned' => $pruned]);

        return self::SUCCESS;
    }

    /** Hapus hanya berkas cadangan buatan perintah ini (`db-*.sql`, `pdf-*.tar.gz`) yang melewati masa retensi. */
    private function prune(string $dir, int $days): int
    {
        $cutoff = now()->subDays($days)->getTimestamp();
        $old = collect([...File::glob("{$dir}/db-*.sql"), ...File::glob("{$dir}/pdf-*.tar.gz")])
            ->filter(fn (string $path) => File::lastModified($path) < $cutoff);

        $old->each(fn (string $path) => File::delete($path));

        return $old->count();
    }
}
