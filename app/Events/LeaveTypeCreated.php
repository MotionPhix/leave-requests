<?php

namespace App\Events;

use App\Models\LeaveType;
use Thunk\Verbs\Event;

class LeaveTypeCreated extends Event
{
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

    public string $created_by_id;

    public function handle()
    {
        $leaveType = LeaveType::create([
            'workspace_id' => $this->workspace_id,
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
        logger('Leave type created', [
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'created_by_id' => $this->created_by_id,
        ]);
    }
}
