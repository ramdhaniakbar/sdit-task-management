<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskUser extends Model
{
    public $table = 'task_user';
    
    protected $fillable = ['user_id', 'task_id'];

    protected $dates = ['created_at', 'updated_at'];
}
