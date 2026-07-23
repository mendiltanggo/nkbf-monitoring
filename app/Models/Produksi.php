<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    use HasFactory;

    protected $table = 'produksi';
    protected $guarded = [];
    public function penjodohan()
    {
        return $this->belongsTo(Penjodohan::class, 'id_penjodohan');
    }
}