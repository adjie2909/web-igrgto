<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $table = 'divisions';

    protected $fillable = [
        'nama_divisi',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
