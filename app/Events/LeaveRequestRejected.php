<?php

namespace App\Events;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Workspace;
use Thunk\Verbs\Event;

class LeaveRequestRejected extends Event
{
    public string $workspace_id;
    public string $workspace_name;
    public string $approver_id;
    public string $approver_name;
    public string $leave_request_id;
    public ?string $rejectionReason;
}
