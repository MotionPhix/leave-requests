<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LeaveRequestStatusUpdated extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(public LeaveRequest $leaveRequest) {}

  public function via($notifiable): array
  {
    return ['mail', 'database', 'broadcast'];
  }

  public function toMail($notifiable): MailMessage
  {
    $status = strtolower($this->leaveRequest->status);
    return (new MailMessage)
      ->subject("Leave Request {$status}")
      ->line("Your leave request has been {$status}.")
      ->line("Type: {$this->leaveRequest->leaveType->name}")
      ->line("From: {$this->leaveRequest->start_date}")
      ->line("To: {$this->leaveRequest->end_date}")
      ->action('View Details', route('leave-requests.show', $this->leaveRequest->uuid))
      ->line($this->leaveRequest->comments ? "Comments: {$this->leaveRequest->comments}" : '');
  }

  public function toDatabase($notifiable): array
  {
    return [
      'leave_request_id' => $this->leaveRequest->id,
      'status' => $this->leaveRequest->status,
      'leave_type' => $this->leaveRequest->leaveType->name,
      'start_date' => $this->leaveRequest->start_date,
      'end_date' => $this->leaveRequest->end_date,
      'comments' => $this->leaveRequest->comments,
    ];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'id' => $this->id,
      'type' => 'leave_request_updated',
      'data' => [
        'leave_request_id' => $this->leaveRequest->id,
        'status' => $this->leaveRequest->status,
        'leave_type' => $this->leaveRequest->leaveType->name,
        'created_at' => now()->diffForHumans(),
      ]
    ]);
  }
}
