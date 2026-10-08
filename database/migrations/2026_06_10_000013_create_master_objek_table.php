<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_objek', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_opd_id');
            $table->unsignedBigInteger('master_lokasi_id');
            $table->string('uraian_objek', 255);
            $table->unsignedBigInteger('master_retribusi_id');
            $table->unsignedBigInteger('master_rincian_retribusi_id');
            $table->unsignedBigInteger('sub_rincian_retribusi')->nullable();
            $table->string('rincian_objek', 255);
            $table->string('sub_rincian_objek', 255);
            $table->text('images')->nullable();
            $table->unsignedBigInteger('master_satuan_id');
            $table->unsignedBigInteger('master_tpj_id');
            $table->decimal('harga_objek', 18,  2);
            $table->decimal('harga_objek_asing', 18,  2)->nullable();
            $table->unsignedBigInteger('master_currency_id');
            $table->integer('master_rules_id');
            $table->unsignedBigInteger('master_role_id');
            $table->unsignedBigInteger('master_status_objek_id');
            $table->integer('master_type_object_id');
            $table->string('required_document', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_objek');
    }
};
