<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_lokasi_objek', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_opd_id');
            $table->string('nama_lokasi', 255);
            $table->integer('location_status')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_lokasi_objek');
    }
};
