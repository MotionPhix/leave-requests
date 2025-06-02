<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LeaveRequestSubmitted extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(public LeaveRequest $leaveRequest) {}

  public function via($notifiable): array
  {
    return ['mail', 'database', 'broadcast'];
  }

  public function toMail($notifiable): MailMessage
  {
    $user = $this->leaveRequest->user;
    return (new MailMessage)
      ->subject("New Leave Request from {$user->name}")
      ->line("{$user->name} has submitted a leave request.")
      ->line("Type: {$this->leaveRequest->leaveType->name}")
      ->line("From: {$this->leaveRequest->start_date}")
      ->line("To: {$this->leaveRequest->end_date}")
      ->action('View Request', route('admin.leave-requests.show', $this->leaveRequest->uuid))
      ->line('Please review this request at your earliest convenience.');
  }

  public function toDatabase($notifiable): array
  {
    return [
      'leave_request_id' => $this->leaveRequest->id,
      'user_name' => $this->leaveRequest->user->name,
      'leave_type' => $this->leaveRequest->leaveType->name,
      'start_date' => $this->leaveRequest->start_date,
      'end_date' => $this->leaveRequest->end_date,
    ];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'id' => $this->id,
      'type' => 'leave_request_submitted',
      'data' => [
        'leave_request_id' => $this->leaveRequest->id,
        'user_name' => $this->leaveRequest->user->name,
        'leave_type' => $this->leaveRequest->leaveType->name,
        'created_at' => now()->diffForHumans(),
      ]
    ]);
  }
}
