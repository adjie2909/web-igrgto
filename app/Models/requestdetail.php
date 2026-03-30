<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class requestdetail extends Model
{
    protected $table = 'request_details';
    protected $fillable = [
        'request_id',
        'barang_id',
        'qty',
        'keterangan',
        'image',
        'harga_manual',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
