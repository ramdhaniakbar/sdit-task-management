<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest\UpdateUserRequest;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function user_profile(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if (!$token) {
            return response()->json([
                'status' => 404,
                'message' => 'No active token found'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'User profile',
            'data' => [
                'user' => Auth::user(),
            ]
        ], 200);
    }

    public function update_profile(UpdateUserRequest $request)
    {
        // validate request

        // get user
        $user = User::find(Auth::id());

        // update user
        $user->name = $request->name;
        $user->email = $request->email;
        
        // update additional columns
        $user->phone = $request->phone;
        $user->date_of_birth = $request->date_of_birth;
        $user->gender = $request->gender;
        $user->address = $request->address;

        if ($request->old_password || $request->password) {
            try {
                $request->validate([
                    'old_password' => 'required|string',
                    'password' => 'required|string|min:8|confirmed',
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Old password is incorrect'
                ], 401);
            } else if (password_verify($request->password, $user->password)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'New password must be different from old password'
                ], 400);
            } else if (password_verify($request->old_password, $user->password)) {
                $user->password = Hash::make($request->password);
            }
        }

        if ($request->hasFile('photo')) {
            // check if old photo exists
            if ($user->photo && Storage::disk('public')->exists('images/user/' . $user->photo)) {
                // delete old photo
                Storage::disk('public')->delete('images/user/' . $user->photo);
            }

            // upload new photo
            $photo = $request->file('photo');
            $photo_name = Str::random(10) . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs('images/user', $photo_name, 'public');
            $user->photo = $photo_name;
        }

        $user->save();

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has updated their profile',
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'User profile updated successfully',
            'data' => [
                'user' => $user,
            ]
        ], 200);
    }
}
