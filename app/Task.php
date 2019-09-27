<?php

namespace App;

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
}
