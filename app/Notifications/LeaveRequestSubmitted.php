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

  public function toMail(object $notifiable): MailMessage
  {
    $url = route('tenant.admin.leave-requests.show', [
      'tenant_slug' => $this->leaveRequest->workspace->slug,
      'tenant_uuid' => $this->leaveRequest->workspace->uuid,
      'leaveRequest' => $this->leaveRequest->uuid,
    ]);

    return (new MailMessage)
      ->subject('New Leave Request Submitted')
      ->line("A new {$this->leaveRequest->leaveType->name} request has been submitted by {$this->leaveRequest->user->name}.")
      ->line("Duration: {$this->leaveRequest->start_date->format('M j, Y')} to {$this->leaveRequest->end_date->format('M j, Y')}")
      ->action('Review Request', $url)
      ->line('Please review and take appropriate action.');
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
      'message' => "{$this->leaveRequest->user->name} has submitted a {$this->leaveRequest->leaveType->name} request",
    ];
  }

  public function toBroadcast($notifiable): BroadcastMessage
  {
    return new BroadcastMessage([
      'type' => 'leave_request_submitted',
      'title' => 'New Leave Request',
      'message' => "{$this->leaveRequest->user->name} has submitted a {$this->leaveRequest->leaveType->name} request",
      'action_url' => auth()->user()->hasRole(['Admin', 'HR'])
        ? route('admin.leave-requests.show', $this->leaveRequest->uuid)
        : null,
      'data' => $this->toArray($notifiable),
    ]);
  }
}
