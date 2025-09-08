<?php

namespace App\Listeners;

use App\Events\LeaveRequestApproved;
use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestApproved as LeaveRequestApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyEmployeeOfApproval implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LeaveRequestApproved $event): void
    {
        // Load the leave request from the event ID
        $leaveRequest = LeaveRequest::find($event->leave_request_id);

        if (!$leaveRequest) {
            return;
        }

        $leaveRequest->user->notify(
            new LeaveRequestApprovedNotification($leaveRequest)
        );
    }
}
