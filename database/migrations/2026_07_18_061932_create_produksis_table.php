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
        Schema::create('produksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_penjodohan')->constrained('penjodohan')->onDelete('cascade');
            $table->date('tanggal_bertelur');
            $table->integer('jumlah_telur')->default(0);
            $table->date('tanggal_menetas_prediksi')->nullable();
            $table->date('tanggal_menetas_aktual')->nullable();
            $table->integer('jumlah_menetas')->default(0);
            $table->integer('jumlah_gagal')->default(0);
            $table->date('tanggal_panen_piyik')->nullable();
            $table->enum('status', ['bertelur', 'mengerami', 'menetas', 'panen', 'gagal'])->default('bertelur');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksis');
    }
};
