<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class barang extends Model
{
    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'fraction',
        'unit',
        'stok',
        'harga_estimasi'
    ];
}
