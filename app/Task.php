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
        'attachmentType',
    ];

    protected $attributes = [
        'description' => '',
        'state' => 'pending',
        'attachment' => '',
        'attachmentType' => '',
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

    public static function getFileExtension($mimetype)
    {

        $fileExtensions = [
            'image/jpeg'        => '.jpeg',
            'image/png'         => '.png',
            'application/pdf'   => '.pdf',
            'text/plain'        => '.txt',
        ];

        if (Arr::has($fileExtensions, $mimetype)){
            return $fileExtensions[$mimetype];
        }

        return false;
    }
}
