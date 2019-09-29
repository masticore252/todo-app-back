<?php

namespace App;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Task extends Model
{
    protected $fillable = [
        'description',
        'state',
        'attachment',
        'attachment_type',
    ];

    protected $attributes = [
        'description' => '',
        'state' => 'pending',
        'attachment' => '',
        'attachment_type' => '',
    ];

    protected $fileExtensions = [
        'image/jpeg'        => 'jpeg',
        'image/png'         => 'png',
        'application/pdf'   => 'pdf',
    ];

    public function validate()
    {
        $rules = [
            'description' => 'string|max:100',
            'state' => 'in:pending,done'
        ];

        $validator = validator( $this->attributes, $rules );

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->messages());
        }

        return true;
    }

    public function getFilenameAttribute()
    {
        if (Arr::has($this->fileExtensions, $this->attachment_type)){
            return "{$this->attachment}.{$this->fileExtensions[$this->attachment_type]}";
        }

        \Log::warning('task has an attachment with unknown mediatype', [
            'task' => $this->toArray(),
        ]);
        return false;
    }
}
