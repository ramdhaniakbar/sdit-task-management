<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(10);
        return response()->json([
            'status' => 200,
            'message' => 'Successfully fetched notifications',
            'data' => $notifications,
        ], 200);
    }

    public function mark_as_read($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if (!$notification) {
            return response()->json([
                'status' => 404,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->update(['status' => 'read']);
        return response()->json([
            'status' => 200,
            'message' => 'Notification marked as read',
        ], 200);
    }
}
