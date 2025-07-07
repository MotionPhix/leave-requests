<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
  public function index()
  {
    $user = auth()->user();
    $notifications = $user->notifications()
      ->latest()
      ->paginate(10)
      ->through(function ($notification) {
        return $this->formatNotification($notification);
      });

    return Inertia::render('notifications/Index', [
      'notifications' => $notifications,
      'unreadCount' => $user->unreadNotifications()->count(),
    ]);
  }

  public function getNotifications(): JsonResponse
  {
    $user = auth()->user();
    $notifications = $user->notifications()
      ->latest()
      ->limit(20)
      ->get()
      ->map(function ($notification) {
        return $this->formatNotification($notification);
      });

    return response()->json([
      'notifications' => $notifications,
      'unreadCount' => $user->unreadNotifications()->count(),
    ]);
  }

  public function markAsRead(Request $request, $id): JsonResponse
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

  private function formatNotification($notification): array
  {
    $data = $notification->data;

    $uuid = $data['leave_request_uuid'] ?? 'No UUID found';

    // Default values
    $title = $notification->type;
    $message = 'You have a new notification';
    $icon = 'bell';
    $priority = 'medium';
    $actionUrl = null;

    // Format based on notification type
    switch ($notification->type) {
      case 'App\\Notifications\\LeaveRequestSubmitted':
        $title = 'New Leave Request';
        $message = "{$data['user_name']} submitted a {$data['leave_type']} request from {$data['start_date']} to {$data['end_date']}";
        $icon = 'calendar-plus';
        $priority = 'medium';
        $actionUrl = route('admin.leave-requests.show', $uuid);
        break;

      case 'App\\Notifications\\LeaveRequestApproved':
        $title = 'Leave Request Approved';
        $message = "Your {$data['leave_type']} request from {$data['start_date']} to {$data['end_date']} has been approved";
        $icon = 'calendar-check';
        $priority = 'high';
        $actionUrl = route('leave-requests.show', $uuid);
        break;

      case 'App\\Notifications\\LeaveRequestRejected':
        $title = 'Leave Request Rejected';
        $message = "Your {$data['leave_type']} request from {$data['start_date']} to {$data['end_date']} has been rejected";
        $icon = 'calendar-x';
        $priority = 'high';
        $actionUrl = route('leave-requests.show', $data['leave_request_uuid']);
        break;

      case 'App\\Notifications\\LeaveRequestUpdated':
        $title = 'Leave Request Updated';
        $message = "Leave request for {$data['user_name']} has been updated";
        $icon = 'calendar-clock';
        $priority = 'medium';
        $actionUrl = route('admin.leave-requests.show', $uuid);
        break;
    }

    return [
      'id' => $notification->id,
      'type' => $notification->type,
      'title' => $title,
      'message' => $message,
      'data' => $data,
      'read_at' => $notification->read_at,
      'created_at' => $notification->created_at->diffForHumans(),
      'action_url' => $actionUrl,
      'icon' => $icon,
      'priority' => $priority,
    ];
  }

  private function formatDateRange(string $start, string $end): string
  {
    $startDate = \Carbon\Carbon::parse($start);
    $endDate = \Carbon\Carbon::parse($end);

    if ($startDate->isSameDay($endDate)) {
      return $startDate->format('M j, Y');
    }

    return $startDate->format('M j') . ' - ' . $endDate->format('M j, Y');
  }

  public function deleteNotification($id): JsonResponse
  {
    $notification = auth()->user()->notifications()->findOrFail($id);
    $notification->delete();

    return response()->json([
      'message' => 'Notification deleted successfully',
      'unreadCount' => auth()->user()->unreadNotifications()->count(),
    ]);
  }

  public function clearAllNotifications(): JsonResponse
  {
    auth()->user()->notifications()->delete();

    return response()->json([
      'message' => 'All notifications cleared successfully',
      'unreadCount' => 0,
    ]);
  }
}
