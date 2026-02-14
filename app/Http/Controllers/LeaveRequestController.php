<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function downloadPdf(LeaveRequest $leaveRequest)
    {
        if (Auth::user()->employee->id !== $leaveRequest->employee_id &&
            ! in_array(Auth::user()->employee->role->value, ['MANAGER', 'HRD'])) {
            abort(403);
        }

        $pdf = Pdf::loadView('pdf.leave-request', [
            'record' => $leaveRequest,
            'employee' => $leaveRequest->employee,
        ]);

        return $pdf->download('leave-request-'.$leaveRequest->id.'.pdf');
    }
}
