<?php

use App\Enums\EmployeeRole;
use App\Http\Controllers\LeaveRequestController;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/leave-requests/{record}/attachment', function (LeaveRequest $record) {
    $user = Auth::user();
    $employee = $user->employee;

    if (! $employee || (
        $employee->id !== $record->employee_id
        && ! in_array($employee->role, [EmployeeRole::ADMIN, EmployeeRole::MANAGER, EmployeeRole::HRD])
    )) {
        abort(403);
    }

    if (! Storage::exists($record->attachment_path)) {
        abort(404);
    }

    return Storage::response($record->attachment_path);

})->middleware('auth')->name('leave-requests.attachment.view');

Route::get('/leave-requests/{leaveRequest}/pdf', [LeaveRequestController::class, 'downloadPdf'])
    ->name('leave-requests.download-pdf')
    ->middleware('auth');
