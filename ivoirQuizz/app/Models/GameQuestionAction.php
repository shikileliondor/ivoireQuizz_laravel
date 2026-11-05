<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameQuestionAction extends Model
{
    protected $guarded = [];
    protected $casts = ['paused_until' => 'datetime', 'cost' => 'integer'];
}
