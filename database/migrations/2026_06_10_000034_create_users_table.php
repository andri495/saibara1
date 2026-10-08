<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('address', 255);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->unsignedBigInteger('master_opd_id')->nullable();
            $table->unsignedBigInteger('master_lokasi_id');
            $table->unsignedBigInteger('master_role_id');
            $table->string('no_telp', 255);
            $table->string('nama_instansi', 255)->nullable();
            $table->string('profile_pict', 255)->nullable();
            $table->string('npwrd', 255);
            $table->dateTime('email_verified_at')->nullable();
            $table->string('remember_token', 255)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
