<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kandang extends Model
{
    use HasFactory;

    // Beritahu Laravel nama tabel yang benar
    protected $table = 'kandang';

    // Izinkan semua kolom diisi data (Mass Assignment)
    protected $guarded = [];
}