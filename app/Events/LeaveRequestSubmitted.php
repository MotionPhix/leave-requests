<?php

namespace App\Events;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Workspace;
use Thunk\Verbs\Event;

class LeaveRequestSubmitted extends Event
{
    public string $workspace_id;
    public string $workspace_name;
    public string $employee_id;
    public string $employee_name;
    public string $leave_request_id;
    public array $requestData;
}
