<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'artifact_types',
    ];

    protected $casts = [
        'artifact_types' => 'array',
    ];
}
