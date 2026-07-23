<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penjodohan', function (Blueprint $table) {
            $table->id();
            // Foreign keys ke Kandang dan Burung
            $table->foreignId('id_kandang')->constrained('kandang')->onDelete('restrict');
            $table->foreignId('id_burung_jantan')->constrained('burung')->onDelete('restrict');
            $table->foreignId('id_burung_betina')->constrained('burung')->onDelete('restrict');
            
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjodohans');
    }
};
