<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function downloadPdf(LeaveRequest $leaveRequest)
    {
        $employee = Auth::user()->employee;

        if (! $employee || (
            $employee->id !== $leaveRequest->employee_id &&
            ! in_array($employee->role->value, ['ADMIN', 'MANAGER', 'HRD'])
        )) {
            abort(403);
        }

        $pdf = Pdf::loadView('pdf.leave-request', [
            'record' => $leaveRequest,
            'employee' => $leaveRequest->employee,
        ]);

        return $pdf->download('leave-request-'.$leaveRequest->id.'.pdf');
    }
}
