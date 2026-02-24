<?php

namespace App\Exports\Sheets;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmployeesSheetExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function query(): Builder
    {
        return Employee::query()
            ->with(['user', 'department']);
    }

    public function title(): string
    {
        return 'Employees';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Full Name',
            'Department',
            'Role',
            'Gender',
            'Phone Number',
            'Address',
            'Remaining Leave Days',
            'Joined At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->user?->name ?? 'N/A',
            $row->department?->name ?? 'N/A',
            $row->role?->value ?? $row->role,
            $row->gender,
            $row->phone_number,
            $row->address,
            $row->remaining_leave_days,
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
