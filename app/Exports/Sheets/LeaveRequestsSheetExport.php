<?php

namespace App\Exports\Sheets;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeaveRequestsSheetExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function query(): Builder
    {
        return LeaveRequest::query()
            ->with(['employee.user'])
            ->where('created_at', '>=', now()->subDays(30));
    }

    public function title(): string
    {
        return 'Leave Requests';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee Name',
            'Type',
            'Start Date',
            'End Date',
            'Status',
            'Reason',
            'Requested At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->employee?->user?->name ?? 'N/A',
            $row->type?->value ?? $row->type,
            $row->start_date,
            $row->end_date,
            $row->status?->value ?? $row->status,
            $row->reason,
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
