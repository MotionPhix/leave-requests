<?php

namespace App\Listeners;

use App\Events\LeaveRequestRejected;
use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestRejected as LeaveRequestRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyEmployeeOfRejection implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LeaveRequestRejected $event): void
    {
        // Load the leave request from the event ID
        $leaveRequest = LeaveRequest::find($event->leave_request_id);

        if (!$leaveRequest) {
            return;
        }

        $leaveRequest->user->notify(
            new LeaveRequestRejectedNotification($leaveRequest)
        );
    }
}
