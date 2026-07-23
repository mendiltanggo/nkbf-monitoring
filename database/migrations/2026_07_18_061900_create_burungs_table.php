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
        Schema::create('burung', function (Blueprint $table) {
            $table->id();
            $table->string('no_ring', 100)->unique();
            $table->string('nama', 100)->nullable();
            $table->enum('jenis_kelamin', ['jantan', 'betina']);
            
            // Self-referencing untuk trah/silsilah
            $table->unsignedBigInteger('id_induk_jantan')->nullable();
            $table->unsignedBigInteger('id_induk_betina')->nullable();
            $table->unsignedBigInteger('id_kandang')->nullable();
            
            $table->date('tanggal_menetas')->nullable();
            // Tambahkan 'trotolan' di dalam array ENUM
            $table->enum('status_kondisi', ['trotolan', 'siap_produksi', 'mabung', 'sakit', 'terjual', 'mati'])->default('trotolan');
            $table->text('prestasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Relasi Silsilah
            $table->foreign('id_induk_jantan')->references('id')->on('burung')->onDelete('set null');
            $table->foreign('id_induk_betina')->references('id')->on('burung')->onDelete('set null');
            $table->foreign('id_kandang')->references('id')->on('kandang')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('burungs');
    }
};
