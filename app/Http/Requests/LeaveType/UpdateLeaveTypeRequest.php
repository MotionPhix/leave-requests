<?php

namespace App\Http\Requests\LeaveType;

class UpdateLeaveTypeRequest extends StoreLeaveTypeRequest
{
  public function rules(): array
  {
    $rules = parent::rules();
    $rules['name'] = [
      'required',
      'string',
      'max:255',
      'unique:leave_types,name,' . $this->route('leaveType')->id
    ];

    return $rules;
  }
}
