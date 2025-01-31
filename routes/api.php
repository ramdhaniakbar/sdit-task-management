<?php

use App\Http\Controllers\AuthController;
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
});
