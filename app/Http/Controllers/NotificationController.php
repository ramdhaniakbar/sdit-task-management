<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(10);
        return response()->json([
            'message' => 'Successfully fetched notifications',
            'data' => $notifications,
        ]);
    }

    public function mark_as_read($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->update(['status' => 'read']);
        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }
}
