<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('retribution_targets', function (Blueprint $table) {
            $table->id();
            $table->integer('master_opd_id');
            $table->integer('master_lokasi_id')->nullable();
            $table->integer('master_retribusi_id');
            $table->integer('master_rincian_retribusi_id')->nullable();
            $table->integer('master_sub_rincian_retribusi_id')->nullable();
            $table->string('tahun');
            $table->decimal('target_amount', 15,  2);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retribution_targets');
    }
};
