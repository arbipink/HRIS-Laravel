
<!DOCTYPE html>
<html>
<head>
    <title>Leave Request Form</title>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .title { font-size: 20px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .signatures { margin-top: 50px; width: 100%; }
        .signature-box { float: left; width: 33%; text-align: center; }
        .line { border-top: 1px solid #000; width: 80%; margin: 50px auto 10px auto; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">OFFICIAL LEAVE REQUEST</div>
        <div>Reference ID: #{{ $record->id }}</div>
    </div>

    <table class="table">
        <tr>
            <th>Employee Name</th>
            <td>{{ $record->employee->user->name }}</td>
        </tr>
        <tr>
            <th>Department</th>
            <td>{{ $record->employee->department->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Leave Type</th>
            <td>{{ $record->type instanceof \UnitEnum ? $record->type->value : $record->type }}</td>
        </tr>
        <tr>
            <th>Reason</th>
            <td>{{ $record->reason }}</td>
        </tr>
        <tr>
            <th>Duration</th>
            <td>
                {{ \Carbon\Carbon::parse($record->start_date)->format('d M Y') }} - 
                {{ \Carbon\Carbon::parse($record->end_date)->format('d M Y') }}
            </td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ $record->status instanceof \UnitEnum ? $record->status->value : $record->status }}</td>
        </tr>
    </table>

    <div class="signatures">
        <div class="signature-box">
            <div>Requested By</div>
            <div class="line"></div>
            <div>{{ $record->employee->user->name }}</div>
        </div>
        <div class="signature-box">
            <div>Approved By (Manager)</div>
            <div class="line"></div>
            <div>{{ $record->manager->user->name ?? 'Pending' }}</div>
        </div>
        <div class="signature-box">
            <div>Approved By (HRD)</div>
            <div class="line"></div>
            <div>{{ $record->hrd->user->name ?? 'Pending' }}</div>
        </div>
    </div>
</body>
</html>