<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_chief', function (Blueprint $table) {
            $table->id();
            $table->string('chief_name', 255);
            $table->string('chief_nip', 255);
            $table->integer('master_opd_id');
            $table->integer('master_lokasi_id');
            $table->integer('master_role_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_chief');
    }
};
