<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectItemChange extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['old_serial_numbers' => 'array', 'new_serial_numbers' => 'array'];
}
