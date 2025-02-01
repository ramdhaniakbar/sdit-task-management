<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'API is running';
});

// public routes (no-token)
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

// protected routes (using-token)
Route::middleware('auth:sanctum')->group(function () {
    // user routes
    Route::get('/user', [UserController::class, 'user_profile'])->name('user.profile');
    Route::put('/user', [UserController::class, 'update_profile'])->name('user.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // tasks routes
    Route::apiResource('/tasks', TaskController::class);
    Route::post('/tasks/{id}/assign', [TaskController::class, 'assign_task'])->name('tasks.assign');

    Route::get('/my-assignments', [TaskController::class, 'my_assignments'])->name('my-assignments');
    Route::get('/assigned-tasks', [TaskController::class, 'assigned_task'])->name('assigned-task');

    Route::post('/tasks/{id}/update-task-status', [TaskController::class, 'update_task_status'])->name('tasks.update-status');

    // notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'mark_as_read'])->name('notifications.mark-as-read');
});
