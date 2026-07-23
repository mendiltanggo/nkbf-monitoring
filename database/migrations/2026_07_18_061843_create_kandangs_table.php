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
        Schema::create('kandang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kandang', 50)->unique();
            $table->enum('kategori', ['ternak', 'anakan', 'gantung', 'alumunium'])->default('ternak');
            $table->enum('status', ['kosong', 'terisi', 'sterilisasi'])->default('kosong');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kandangs');
    }
};
