<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjodohan extends Model
{
    use HasFactory;
    
    protected $table = 'penjodohan';
    protected $guarded = [];

    // Relasi ke tabel Kandang
    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'id_kandang');
    }

    // Relasi ke tabel Burung (Jantan)
    public function jantan()
    {
        return $this->belongsTo(Burung::class, 'id_burung_jantan');
    }

    // Relasi ke tabel Burung (Betina)
    public function betina()
    {
        return $this->belongsTo(Burung::class, 'id_burung_betina');
    }

    /**
     * SENSOR OTOMATISASI STATUS KANDANG
     */
    protected static function booted(): void
    {
        // 1. Saat Pasangan BARU DIBUAT
        static::created(function ($penjodohan) {
            if ($penjodohan->status === 'aktif' && $penjodohan->id_kandang) {
                // Otomatis ubah kandang menjadi 'terisi'
                $penjodohan->kandang()->update(['status' => 'terisi']);
            }
        });

        // 2. Saat Pasangan DIUBAH (Misal: ganti kandang atau cerai)
        static::updated(function ($penjodohan) {
            // Skenario A: Jika status perjodohan berubah dari 'aktif' ke status lain (misal: cerai/mati)
            if ($penjodohan->isDirty('status') && $penjodohan->status !== 'aktif') {
                $penjodohan->kandang()->update(['status' => 'kosong']); 
            }

            // Skenario B: Jika burung PINDAH KANDANG
            if ($penjodohan->isDirty('id_kandang')) {
                // Kandang yang lama dikosongkan
                $kandangLamaId = $penjodohan->getOriginal('id_kandang');
                if ($kandangLamaId) {
                    Kandang::where('id', $kandangLamaId)->update(['status' => 'kosong']);
                }
                
                // Kandang yang baru otomatis 'terisi' (asalkan status perjodohannya masih aktif)
                if ($penjodohan->status === 'aktif' && $penjodohan->id_kandang) {
                    $penjodohan->kandang()->update(['status' => 'terisi']);
                }
            }
        });

        // 3. Saat Pasangan DIHAPUS dari sistem
        static::deleted(function ($penjodohan) {
            if ($penjodohan->id_kandang) {
                // Bebaskan kandang agar berstatus 'kosong' kembali
                $penjodohan->kandang()->update(['status' => 'kosong']);
            }
        });
    }
}