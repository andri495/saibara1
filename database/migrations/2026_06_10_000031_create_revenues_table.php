<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('year')->nullable();
            $table->string('month')->nullable();
            $table->unsignedBigInteger('master_opd_id');
            $table->unsignedBigInteger('master_lokasi_id')->nullable();
            $table->unsignedBigInteger('master_retribusi_id');
            $table->unsignedBigInteger('master_rincian_retribusi_id')->nullable();
            $table->unsignedBigInteger('master_sub_rincian_retribusi_id')->nullable();
            $table->integer('total_transactions');
            $table->decimal('total_revenue', 18,  2);
            $table->string('is_installment');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
