<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest\AssignTaskRequest;
use App\Http\Requests\TaskRequest\StoreTaskRequest;
use App\Http\Requests\TaskRequest\UpdateTaskRequest;
use App\Http\Requests\TaskRequest\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $tasks = Task::where('user_id', $user->id)->paginate(10);

        return response()->json([
            'status' => 200,
            'message' => 'Tasks list',
            'data' => $tasks,
        ], 200);
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
    public function store(StoreTaskRequest $request)
    {
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
            'status' => 201,
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
                'status' => 404,
                'message' => 'Task not found or you do not have permission to view this task',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Task detail',
            'data' => $task,
        ], 200);
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
    public function update(UpdateTaskRequest $request, string $id)
    {
        // get task by id where user_id is auth id
        $user = Auth::user();
        $task = Task::where('id', $id)->where('user_id', $user->id)->first();

        if (!$task) {
            return response()->json([
                'status' => 404,
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
            'status' => 200,
            'message' => 'Task updated successfully',
            'data' => $task,
        ], 200);
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
                'status' => 404,
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
            'status' => 200,
            'message' => 'Task deleted successfully',
        ], 200);
    }

    /**
     * Assign Task to Another User.
     */
    public function assign_task(AssignTaskRequest $request, string $id)
    {
        // get task by id where user_id is auth id
        $user = Auth::user();
        $task = Task::where('id', $id)->where('user_id', $user->id)->first();
        $assignee = User::where('email', $request->email)->first();

        if (!$task) {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found or you do not have permission to assign this task',
            ], 404);
        } else if (!$assignee) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 404);
        } else if ($assignee->id == $user->id) {
            return response()->json([
                'status' => 400,
                'message' => 'You cannot assign task to yourself',
            ], 400);
        } else if (TaskUser::where('task_id', $task->id)->where('user_id', $assignee->id)->exists()) {
            return response()->json([
                'status' => 400,
                'message' => 'Task already assigned to this user',
            ], 400);
        }

        // assign task to another user
        $task->users()->attach($assignee->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // send notification
        $assignee->notifications()->create([
            'sender_id' => $user->id,
            'receiver_id' => $assignee->id,
            'status' => 'unread',
            'note' => 'You have been assigned a task with the title ' . $task->title,
        ]);

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been assigned a task with the title ' . $task->title . ' to ' . $assignee->name,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Task assigned successfully',
            'data' => $task,
        ], 200);
    }

    public function my_assignments()
    {
        $user = Auth::user();

        // check if the user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        // fetch tasks assigned to the user through the pivot table
        $tasks = DB::table('tasks')
                ->join('task_user', 'tasks.id', '=', 'task_user.task_id')
                ->where('task_user.user_id', '=', $user->id)
                ->select(
                    'tasks.id',
                    'tasks.title',
                    'tasks.description',
                    'tasks.due_date',
                    'tasks.created_at',
                    'tasks.updated_at',
                    'task_user.status', 
                    'task_user.created_at as assigned_at', 
                    'task_user.updated_at as last_updated'
                )
                ->paginate(10);

        return response()->json([
            'status' => 200,
            'message' => 'My assigned tasks retrieved successfully',
            'data' => $tasks,
        ], 200);
    }

    public function assigned_task()
    {
        $user = Auth::user();

        // check if the user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        // fetch tasks that are assigned to users other than the logged-in user
        $tasks = Task::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', '!=', $user->id);
        })->with('users')->paginate(10);

        return response()->json([
            'status' => 200,
            'message' => 'Assigned tasks retrieved successfully',
            'data' => $tasks,
        ], 200);
    }

    public function update_task_status(UpdateTaskStatusRequest $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found or you do not have permission to assign this task',
            ], 404);
        }
    
        // check if the task is assigned to the user
        $taskUser = $request->user()->tasks()->where('task_id', $task->id)->first();

        if (!$taskUser) {
            return response()->json([
                'status' => 404,
                'message' => 'Task not assigned to you or you do not have permission to update this task status',
            ], 404);
        }

        // get current status from pivot table
        $currentStatus = $taskUser->pivot->status;

        // define valid status transitions
        $validTransitions = [
            'pending' => ['in progress'],  // pending can only go to in progress
            'in progress' => ['completed'], // in progress can only go to completed
            'completed' => [] // completed is a final state
        ];

        // check if the status transition is valid
        if (!in_array($request->status, $validTransitions[$currentStatus])) {
            return response()->json([
                'status' => 400,
                'message' => "Invalid status transition. You can only change status from '$currentStatus' to '" . implode("', '", $validTransitions[$currentStatus]) . "'",
            ], 400);
        }

        // update task status in the pivot table
        $request->user()->tasks()->updateExistingPivot($task->id, ['status' => $request->status]);

        // user activity log
        UserActivity::create([
            'user_id' => $request->user()->id,
            'activities' => $request->user()->name . ' updated task ' . $task->title . ' status to ' . $request->status,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Your task status has been updated.',
        ], 200);
    }
}
