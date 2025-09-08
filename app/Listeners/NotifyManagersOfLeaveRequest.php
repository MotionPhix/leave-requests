<?php

namespace App\Listeners;

use App\Events\LeaveRequestSubmitted;
use App\Models\LeaveRequest;
use App\Models\Workspace;
use App\Notifications\LeaveRequestSubmitted as LeaveRequestSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

class NotifyManagersOfLeaveRequest implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LeaveRequestSubmitted $event): void
    {
        // Load the models from the event IDs
        $workspace = Workspace::find($event->workspace_id);
        $leaveRequest = LeaveRequest::find($event->leave_request_id);

        if (!$workspace || !$leaveRequest) {
            return;
        }

        // Set the workspace context for role queries
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);

        // Get all managers and HR in this workspace
        $managers = $workspace->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Manager', 'HR', 'Admin', 'Owner']);
            })
            ->where('id', '!=', $event->employee_id) // Don't notify the requester
            ->get();

        if ($managers->isNotEmpty()) {
            Notification::send($managers, new LeaveRequestSubmittedNotification($leaveRequest));
        }
    }
}
