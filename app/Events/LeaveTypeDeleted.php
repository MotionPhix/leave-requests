<?php

namespace App\Events;

use App\Models\LeaveType;
use Thunk\Verbs\Event;

class LeaveTypeDeleted extends Event
{
    public string $leave_type_uuid;

    public string $workspace_id;

    public string $deleted_by_id;

    public function validate()
    {
        $leaveType = LeaveType::where('uuid', $this->leave_type_uuid)->first();

        if (! $leaveType) {
            throw new \Exception('Leave type not found');
        }

        // Ensure leave type belongs to the workspace
        $this->assert(
            $leaveType->workspace_id == $this->workspace_id,
            'Leave type does not belong to workspace'
        );

        // Check if leave type is used in any leave requests
        $this->assert(
            $leaveType->leaveRequests()->count() === 0,
            'Cannot delete leave type as it is being used in leave requests'
        );
    }

    public function handle()
    {
        $leaveType = LeaveType::where('uuid', $this->leave_type_uuid)->firstOrFail();
        $leaveType->delete();

        return $leaveType;
    }

    public function fired()
    {
        logger('Leave type deleted', [
            'leave_type_uuid' => $this->leave_type_uuid,
            'workspace_id' => $this->workspace_id,
            'deleted_by_id' => $this->deleted_by_id,
        ]);
    }
}
