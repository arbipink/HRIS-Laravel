<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;

class AutoFine extends Command
{
    protected $signature = 'app:attendance-cleanup';

    protected $description = 'Auto-fine for missing clock-outs and mark absences using AttendanceService';

    public function handle(AttendanceService $service)
    {
        $this->info('Starting attendance cleanup...');

        $this->info('Checking for missing clock-outs...');
        $service->processAutoFines();

        $this->info('Attendance cleanup completed.');
    }
}
