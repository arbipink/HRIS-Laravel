<?php

namespace App\Exports\Sheets;

use App\Models\Fine;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinesSheetExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function query(): Builder
    {
        return Fine::query()
            ->with(['employee.user'])
            ->where('date', '>=', now()->subDays(30));
    }

    public function title(): string
    {
        return 'Fines';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee Name',
            'Date',
            'Amount',
            'Reason',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->employee?->user?->name ?? 'N/A',
            $row->date,
            $row->amount,
            $row->reason,
        ];
    }
}
