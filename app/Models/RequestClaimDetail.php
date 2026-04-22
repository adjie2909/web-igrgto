<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestClaimDetail extends Model
{
    protected $fillable = [
        'claim_id',
        'request_detail_id',
        'barang_id',
        'qty',
    ];

    public function claim()
    {
        return $this->belongsTo(RequestClaim::class, 'claim_id');
    }

    public function requestDetail()
    {
        return $this->belongsTo(RequestDetail::class, 'request_detail_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
