<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $tasks = Task::where('user_id', $user->id)->get();

        return response()->json([
            'message' => 'Tasks list',
            'data' => $tasks,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date',
        ]);

        $user = Auth::user();

        // create new task
        $task = Task::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
        ]);

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been added a task with the title ' . $task->title,
        ]);

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // get task by id where user_id is auth id
        $user = Auth::user();
        $task = Task::where('id', $id)->where('user_id', $user->id)->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found or you do not have permission to view this task',
            ], 404);
        }

        return response()->json([
            'message' => 'Task detail',
            'data' => $task,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date',
        ]);

        // get task by id where user_id is auth id
        $user = Auth::user();
        $task = Task::where('id', $id)->where('user_id', $user->id)->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found or you do not have permission to update this task',
            ], 404);
        }

        // update task
        $task->title = $request->title;
        $task->description = $request->description;
        $task->due_date = $request->due_date;
        $task->save();

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been updated a task with the title ' . $task->title,
        ]);

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => $task,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // get task by id where user_id is auth id
        $user = Auth::user();
        $task = Task::where('id', $id)->where('user_id', $user->id)->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found or you do not have permission to delete this task',
            ], 404);
        }

        // delete task
        $task->delete();

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been deleted a task with the title ' . $task->title,
        ]);

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * Assign Task to Another User.
     */
    public function assign_task(Request $request, string $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // get task by id where user_id is auth id
        $user = Auth::user();
        $task = Task::where('id', $id)->where('user_id', $user->id)->first();

        if (!$task) {
            return response()->json([
                'message' => 'Task not found or you do not have permission to assign this task',
            ], 404);
        } else if ($request->user_id == $user->id) {
            return response()->json([
                'message' => 'You cannot assign task to yourself',
            ], 400);
        } else if (TaskUser::where('task_id', $task->id)->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'message' => 'Task already assigned to this user',
            ], 400);
        }

        // assign task to another user
        $task->users()->attach($request->user_id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been assigned a task with the title ' . $task->title . ' to user with id ' . $request->user_id,
        ]);

        return response()->json([
            'message' => 'Task assigned successfully',
            'data' => $task,
        ]);
    }
}
