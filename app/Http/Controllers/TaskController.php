<?php

namespace App\Http\Controllers;

use PDOException;

use App\Task;
use App\Http\Resources\TaskResource;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Validator;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filters = $request->input('filters');

        $tasks = Task::all();

        return [
            'data' => TaskResource::collection($tasks),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $task = new Task($request->only('description'));
            $task->validate();
            $task->save();

        } catch (ValidationException $e) {
            return new JsonResponse([ 'errors' => $e->errors() ], 400);
        } catch (PDOException $e) {
            return new JsonResponse([ 'errors' => $e->getMessage() ]);
        }

        return new TaskResource($task);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        $task->state = $request['done'] ? 'done' : 'pending';
        $task->description = $request['description'] ?? $task->description;

        try {
            $task->validate();
            $task->save();
        } catch (ValidationException $e) {
            return new JsonResponse([ 'errors' => $e->errors() ], 400);
        } catch (PDOException $e) {
            return new JsonResponse([ 'errors' => $e->getMessage() ]);
        }

        return new TaskResource($task);
    }

    public function deleteAllDone(DatabaseManager $db)
    {
        try {
            $db->table('tasks')->where('state','done')->delete();
        } catch (PDOException $e) {
            return new JsonResponse([ 'errors' => $e->getMessage() ]);
        }

        return new JsonResponse();
    }

}
