<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusChanged extends Notification implements ShouldQueue
{
  use Queueable;

  public $leaveRequest;
  public $status;

  public function __construct($leaveRequest, $status)
  {
    $this->leaveRequest = $leaveRequest;
    $this->status = $status;
  }

  public function via($notifiable): array
  {
    return ['broadcast', 'database'];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'title' => 'Leave Status Updated',
      'message' => "Your leave request #{$this->leaveRequest->id} has been {$this->status}.",
      'leave_id' => $this->leaveRequest->id,
      'status' => $this->status
    ]);
  }

  public function toArray($notifiable): array
  {
    return [
      'leave_id' => $this->leaveRequest->id,
      'status' => $this->status
    ];
  }
}
