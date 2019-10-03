<?php

namespace App\Http\Controllers;

use PDOException;

use App\Task;
use App\Http\Resources\TaskResource;

use Illuminate\Support\Arr;
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
        try {
            $tasks = Task::all();
        } catch (PDOException $e) {
            return new JsonResponse(['error' => ['error conecting to database']], 500);
        }

        return new JsonResponse([
            'data' => TaskResource::collection($tasks),
            'error' => false,
        ]);
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
            return new JsonResponse([ 'error' => $e->errors() ], 400);
        } catch (PDOException $e) {
            return new JsonResponse([ 'error' => ['error conecting to database']], 500);
        }

        return new JsonResponse([
            'data' => new TaskResource($task),
            'error' => false,
        ]);

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
            return new JsonResponse([ 'error' => $e->errors() ], 400);
        } catch (PDOException $e) {
            return new JsonResponse([ 'error' => ['error conecting to database'] ], 500);
        }

        return new JsonResponse([
            'data' => new TaskResource($task),
            'error' => false,
        ]);
    }

    public function deleteAllDone(FilesystemManager $filesystem)
    {
        try {
            $query = Task::where('state','!=','pending');
            $tasks = $query->get();

            $query->delete();

            // TODO move this to a backgropund job to save request time
            // filesystem operations can be expensive
            $count = 0;
            foreach ($tasks as $task) {
                $filesystem->delete($task->attachment);
                $count++;
            }
        } catch (PDOException $e) {
            return new JsonResponse([ 'error' => ['error deleting tasks, try again later'] ], 500);
        } catch (Exception $e) {
            return new JsonResponse([ 'error' => ["error deleting all the tasks, only {$count} were deleted"] ], 500);
        }

        return new JsonResponse([
            'error' => false,
        ]);
    }

    public function uploadAttachment(Task $task, Request $request)
    {
        $mimetype = $request['mime-type'] ?? 'text/plain';
        $path = 'tasks/';
        $name = 'attachment_'.$task->id.Task::getFileExtension($mimetype);

        $task->attachment = $path.$name;
        $task->attachmentType = $mimetype;

        try {
            if ($request->file('attachment')->isValid()) {
                $request->file('attachment')->storeAs($path, $name);
                $task->save();
            } else{
                throw new Exception("Uploaded file is invalid");
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['error' => 'file could not be uploaded, try again later'], 500);
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
        $filename = Arr::last(explode('/',$task->attachment));

        if ($task && $task->attachment ) {
            try {
                $contents = $filesystem->get($task->attachment);
                $status = 200;
                $headers = [
                    'Content-Type' => $task->attachmentType,
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ];
            } catch (\Throwable $th) {
                $status = 500;
                return new JsonResponse(['error' => ['there was a problem finding the attachment, try again later']], $status);
            }
        }

        return new Response($contents, $status, $headers);
    }
}
