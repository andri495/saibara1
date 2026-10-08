<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_rincian_retribusi', function (Blueprint $table) {
            $table->id();
            $table->string('rincian_retribusi', 255);
            $table->unsignedBigInteger('master_tipe_retribusi_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_rincian_retribusi');
    }
};
