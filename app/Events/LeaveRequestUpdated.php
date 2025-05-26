<?php

namespace App\Events;

use App\Models\LeaveRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LeaveRequestUpdated implements ShouldBroadcast
{
  use SerializesModels, InteractsWithSockets;

  public $leave;

  public function __construct(LeaveRequest $leave)
  {
    $this->leave = $leave->load('user');
  }

  public function broadcastOn(): Channel
  {
    return new Channel('leave-requests');
  }

  public function broadcastWith(): array
  {
    return [
      'id' => $this->leave->id,
      'title' => $this->leave->user->name . ' - ' . $this->leave->type,
      'start' => $this->leave->start_date,
      'end' => $this->leave->end_date,
      'color' => match ($this->leave->status) {
        'approved' => '#4CAF50',
        'rejected' => '#F44336',
        'pending' => '#FFC107',
      },
      'extendedProps' => [
        'status' => $this->leave->status,
        'type' => $this->leave->type,
      ]
    ];
  }
}

