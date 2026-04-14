<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestClaim extends Model
{
    protected $fillable = [
        'request_id',
        'user_id',
        'tanggal_claim',
        'nomor_claim',
        'status',
        'processed_by',
        'processed_at',
        'completed_by',
        'completed_at',
        'reject_reason',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'tanggal_claim' => 'date',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function requestHeader()
    {
        return $this->belongsTo(RequestHeader::class, 'request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(RequestClaimDetail::class, 'claim_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
