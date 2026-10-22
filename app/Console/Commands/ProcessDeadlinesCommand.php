<?php

namespace App\Console\Commands;

use App\Actions\Deadlines\ProcessDeadlines;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'simanabos:process-deadlines', description: 'Kirim pengingat tenggat dan tandai laporan terlambat')]
class ProcessDeadlinesCommand extends Command
{
    public function handle(ProcessDeadlines $process): int
    {
        $result = $process->handle();
        $this->info("Pengingat terkirim: {$result['reminded']}; ditandai terlambat: {$result['late']}.");

        return self::SUCCESS;
    }
}
