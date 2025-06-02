<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
  public function index()
  {
    $user = auth()->user();
    $notifications = $user->notifications()
      ->latest()
      ->paginate(10)
      ->through(function ($notification) {
        return [
          'id' => $notification->id,
          'type' => $this->getReadableType($notification->type),
          'data' => $notification->data,
          'read_at' => $notification->read_at,
          'created_at' => $notification->created_at->diffForHumans(),
          'action_url' => $this->getActionUrl($notification),
        ];
      });

    return inertia('notifications/Index', [
      'notifications' => $notifications,
      'unreadCount' => $user->unreadNotifications()->count(),
    ]);
  }

  public function getNotifications(): JsonResponse
  {
    return response()->json([
      'notifications' => $this->getFormattedNotifications(),
      'unreadCount' => auth()->user()->unreadNotifications()->count(),
    ]);
  }

  public function markAsRead($id): JsonResponse
  {
    $notification = auth()->user()->notifications()->findOrFail($id);
    $notification->markAsRead();

    return response()->json([
      'message' => 'Notification marked as read',
      'unreadCount' => auth()->user()->unreadNotifications()->count(),
    ]);
  }

  public function markAllAsRead(): JsonResponse
  {
    auth()->user()->unreadNotifications->markAsRead();

    return response()->json([
      'message' => 'All notifications marked as read',
      'unreadCount' => 0,
    ]);
  }

  private function getReadableType(string $type): string
  {
    return str_replace(
      ['App\\Notifications\\', '\\'],
      ['', ' '],
      $type
    );
  }

  private function getActionUrl($notification): ?string
  {
    // Handle different notification types
    return match ($notification->type) {
      'App\Notifications\LeaveRequestSubmitted' =>
      auth()->user()->hasRole(['Admin', 'HR'])
        ? route('admin.leave-requests.show', $notification->data['leave_request_id'])
        : null,
      'App\Notifications\LeaveRequestStatusUpdated' =>
      route('leave-requests.show', $notification->data['leave_request_id']),
      default => null,
    };
  }

  private function getFormattedNotifications()
  {
    return auth()->user()->notifications()
      ->latest()
      ->paginate(10)
      ->through(function ($notification) {
        return [
          'id' => $notification->id,
          'type' => $this->getReadableType($notification->type),
          'data' => $notification->data,
          'read_at' => $notification->read_at,
          'created_at' => $notification->created_at->diffForHumans(),
          'action_url' => $this->getActionUrl($notification),
        ];
      });
  }
}
