<?php

namespace App\Observers;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;

class LeaveRequestObserver
{
    /**
     * Handle the LeaveRequest "created" event.
     */
    public function created(LeaveRequest $leaveRequest): void
    {
        //
    }

    /**
     * Handle the LeaveRequest "updated" event.
     */
    public function updated(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->isDirty('status') &&
            $leaveRequest->status === LeaveStatus::APPROVED &&
            $leaveRequest->type === LeaveType::ANNUAL
        ) {
            $this->decrementEmployeeLeave($leaveRequest);
        }
    }

    /**
     * Handle the LeaveRequest "deleted" event.
     */
    public function deleted(LeaveRequest $leaveRequest): void
    {
        //
    }

    /**
     * Handle the LeaveRequest "restored" event.
     */
    public function restored(LeaveRequest $leaveRequest): void
    {
        //
    }

    /**
     * Handle the LeaveRequest "force deleted" event.
     */
    public function forceDeleted(LeaveRequest $leaveRequest): void
    {
        //
    }

    protected function decrementEmployeeLeave(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;

        if (! $employee) {
            return;
        }

        $startDate = Carbon::parse($leaveRequest->start_date);
        $endDate = Carbon::parse($leaveRequest->end_date);

        $daysTaken = $startDate->diffInDays($endDate) + 1;

        $newBalance = $employee->remaining_leave_days - $daysTaken;

        $employee->update([
            'remaining_leave_days' => max(0, $newBalance),
        ]);
    }
}
