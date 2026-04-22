<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestDetail extends Model
{
    protected $table = 'request_details';
    protected $fillable = [
        'request_id',
        'barang_id',
        'qty',
        'qty_original',
        'keterangan',
        'image',
        'harga_manual',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function requestHeader()
    {
        return $this->belongsTo(RequestHeader::class, 'request_id');
    }

    public function claimDetails()
    {
        return $this->hasMany(RequestClaimDetail::class, 'request_detail_id');
    }
}
