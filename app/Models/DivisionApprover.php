<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DivisionApprover extends Model
{
    protected $fillable = ['role', 'division_id', 'user_id'];
}
