<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class requestheader extends Model
{
    protected $table = 'request_headers';

    protected $fillable = [
        'user_id',
        'tanggal_request',
        'status',
        'current_approval_level',
        'nomor_dokumen',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function details()
    {
        return $this->hasMany(RequestDetail::class, 'request_id');
    }
}
