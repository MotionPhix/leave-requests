<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
  public function index(Request $request)
  {
    $notifications = $request->user()->notifications()->limit(10)->get();
    $unreadCount = $request->user()->unreadNotifications()->count();

    return response()->json([
      'notifications' => $notifications,
      'unread' => $unreadCount,
    ]);
  }

  public function markAsRead(Request $request)
  {
    $request->user()->unreadNotifications->markAsRead();
    return response()->json(['success' => true]);
  }
}
