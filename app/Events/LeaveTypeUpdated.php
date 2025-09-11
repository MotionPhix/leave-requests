<?php

namespace App\Events;

use App\Models\LeaveType;
use Thunk\Verbs\Event;

class LeaveTypeUpdated extends Event
{
    public string $leave_type_uuid;

    public string $workspace_id;

    public string $name;

    public ?string $description;

    public int $max_days_per_year;

    public bool $requires_documentation;

    public bool $gender_specific;

    public string $gender;

    public int $frequency_years;

    public float $pay_percentage;

    public int $minimum_notice_days;

    public bool $allow_negative_balance;

    public string $updated_by_id;

    public function handle()
    {
        $leaveType = LeaveType::where('uuid', $this->leave_type_uuid)->firstOrFail();

        // Ensure leave type belongs to the workspace
        if ($leaveType->workspace_id != $this->workspace_id) {
            throw new \Exception('Leave type does not belong to workspace');
        }

        $leaveType->update([
            'name' => $this->name,
            'description' => $this->description,
            'max_days_per_year' => $this->max_days_per_year,
            'requires_documentation' => $this->requires_documentation,
            'gender_specific' => $this->gender_specific,
            'gender' => $this->gender,
            'frequency_years' => $this->frequency_years,
            'pay_percentage' => $this->pay_percentage,
            'minimum_notice_days' => $this->minimum_notice_days,
            'allow_negative_balance' => $this->allow_negative_balance,
        ]);

        return $leaveType;
    }

    public function fired()
    {
        logger('Leave type updated', [
            'leave_type_uuid' => $this->leave_type_uuid,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'updated_by_id' => $this->updated_by_id,
        ]);
    }
}
