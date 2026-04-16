<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    protected $table = 'divisions';

    protected $fillable = [
        'nama_divisi',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
