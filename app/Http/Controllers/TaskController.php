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
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Database\DatabaseManager;

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
            'error' => false,
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

        return [
            'data' => new TaskResource($task),
            'error' => false,
        ];

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

        return [
            'data' => new TaskResource($task),
            'error' => false,
        ];
    }

    public function deleteAllDone(DatabaseManager $db)
    {
        try {
            $db->table('tasks')->where('state','done')->delete();
        } catch (PDOException $e) {
            return new JsonResponse([ 'errors' => $e->getMessage() ]);
        }

        return [
            'data' => new JsonResponse(),
            'error' => false,
        ];
    }

    public function uploadAttachment(Task $task, Request $request)
    {
        $name = '01';
        $path = "tasks/{$task->id}/01";

        $task->attachment = $name;
        $task->attachment_type = $request->header('Content-Type') ?? 'text/plain';

        try {
            if ($request->file('attachment')->isValid()) {

                $request->file('attachment')->storeAs($path, "01");
                $task->save();

            }
        } catch (\Throwable $th) {
            return new JsonResponse(['errors' => 'file could not be uploaded, try again later'], 500);
        }

        return new JsonResponse([
            'data' => [],
            'error' => false,
        ]);
    }

    public function downloadAttachment(Task $task, FilesystemManager $filesystem)
    {
        $contents = null;
        $status = 404;
        $headers = [];

        if ($task && $task->attachment ) {
            try {
                $contents = $filesystem->get("tasks/{$task->id}/$task->attachment");
                $status = 200;
                $headers = [
                    'Content-Type' => $task->attachment_type,
                    'Content-Disposition' => "attachment; filename=\"{$task->filename}\"",
                ];
            } catch (\Throwable $th) {
                $status = 500;
                return new JsonResponse(['errors' => ['there was a problem finding the attachment, try again later']], $status);
            }
        }

        return new Response($contents, $status, $headers);
    }
}
