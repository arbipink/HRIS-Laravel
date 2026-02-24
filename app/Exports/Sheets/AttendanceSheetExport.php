<?php

namespace App\Exports\Sheets;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendanceSheetExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function query(): Builder
    {
        return Attendance::query()
            ->with(['employee.user'])
            ->where('date', '>=', now()->subDays(30));
    }

    public function title(): string
    {
        return 'Attendance';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee Name',
            'Date',
            'Clock In',
            'Clock Out',
            'Status',
            'Notes',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->employee?->user?->name ?? 'N/A',
            $row->date,
            $row->clock_in,
            $row->clock_out,
            $row->status?->value ?? $row->status,
            $row->notes,
        ];
    }
}
