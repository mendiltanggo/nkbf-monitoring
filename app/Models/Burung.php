<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Burung extends Model
{
    use HasFactory;

    // Beritahu Laravel nama tabel yang benar
    protected $table = 'burung';

    // Izinkan semua kolom diisi data (Mass Assignment)
    protected $guarded = [];
    public function indukJantan()
    {
        return $this->belongsTo(Burung::class, 'id_induk_jantan');
    }

    // Relasi untuk mengambil data Ibu (Betina)
    public function indukBetina()
    {
        return $this->belongsTo(Burung::class, 'id_induk_betina');
    }
    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'id_kandang'); 
    }
}