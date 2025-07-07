<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestRejected extends Notification
{
  use Queueable;

  public function __construct(
    public LeaveRequest $leaveRequest
  ) {}

  public function via(object $notifiable): array
  {
    return ['database', 'broadcast'];
  }

  public function toMail(object $notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject('Leave Request Rejected')
      ->line("We regret to inform you that your {$this->leaveRequest->leaveType->name} request has been rejected.")
      ->line("Duration: {$this->leaveRequest->start_date->format('M j, Y')} to {$this->leaveRequest->end_date->format('M j, Y')}")
      ->action('View Request', route('leave-requests.show', $this->leaveRequest->uuid))
      ->line('Please contact HR if you have any questions.');
  }

  public function toArray(object $notifiable): array
  {
    return [
      'leave_request_id' => $this->leaveRequest->id,
      'leave_request_uuid' => $this->leaveRequest->uuid, // Add UUID here
      'user_name' => $this->leaveRequest->user->name,
      'user_id' => $this->leaveRequest->user->id,
      'leave_type' => $this->leaveRequest->leaveType->name,
      'start_date' => $this->leaveRequest->start_date->toDateString(),
      'end_date' => $this->leaveRequest->end_date->toDateString(),
      'status' => $this->leaveRequest->status,
      'message' => "Your {$this->leaveRequest->leaveType->name} request has been rejected",
    ];
  }

  public function toBroadcast(object $notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'type' => 'leave_request_rejected',
      'title' => 'Leave Request Rejected',
      'message' => "Your {$this->leaveRequest->leaveType->name} request has been rejected",
      'action_url' => route('leave-requests.show', $this->leaveRequest->uuid),
      'data' => $this->toArray($notifiable),
    ]);
  }
}
