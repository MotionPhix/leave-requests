<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestUpdated extends Notification
{
  use Queueable;

  public function __construct(public LeaveRequest $leaveRequest) {}

  public function via($notifiable): array
  {
    return ['mail', 'database', 'broadcast'];
  }

  public function toMail(object $notifiable): MailMessage
  {
    $statusText = match($this->leaveRequest->status) {
      'approved' => 'approved',
      'rejected' => 'rejected',
      'pending' => 'updated and is pending review',
      default => 'updated'
    };

    return (new MailMessage)
      ->subject('Leave Request Status Updated')
      ->line("Your {$this->leaveRequest->leaveType->name} request has been {$statusText}.")
      ->line("Duration: {$this->leaveRequest->start_date->format('M j, Y')} to {$this->leaveRequest->end_date->format('M j, Y')}")
      ->action('View Request', route('leave-requests.show', $this->leaveRequest->uuid))
      ->line('Thank you for using our application!');
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
      'message' => "Your {$this->leaveRequest->leaveType->name} request has been updated",
    ];
  }

  public function toBroadcast(object $notifiable): BroadcastMessage
  {
    $type = match($this->leaveRequest->status) {
      'approved' => 'leave_request_approved',
      'rejected' => 'leave_request_rejected',
      default => 'leave_request_updated'
    };

    $message = match($this->leaveRequest->status) {
      'approved' => "Your {$this->leaveRequest->leaveType->name} request has been approved",
      'rejected' => "Your {$this->leaveRequest->leaveType->name} request has been rejected",
      default => "Your {$this->leaveRequest->leaveType->name} request has been updated"
    };

    return new BroadcastMessage([
      'type' => $type,
      'title' => 'Leave Request Updated',
      'message' => $message,
      'action_url' => route('leave-requests.show', $this->leaveRequest->uuid),
      'data' => $this->toArray($notifiable),
    ]);
  }
}
