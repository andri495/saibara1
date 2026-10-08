<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 255);
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('master_tipe_pembayaran_id')->nullable();
            $table->unsignedBigInteger('master_status_pembayaran_id');
            $table->string('transaction_type', 255);
            $table->string('npwrd', 255);
            $table->string('subject_name', 255);
            $table->string('subject_address', 255);
            $table->string('nama_instansi', 255)->nullable();
            $table->string('no_telp', 255);
            $table->decimal('penalty_amount', 12,  2)->nullable();
            $table->decimal('total_amount', 18,  2);
            $table->dateTime('registration_date');
            $table->dateTime('payment_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->string('transaction_response', 255)->nullable();
            $table->text('document_payment_link')->nullable();
            $table->string('va', 255)->nullable();
            $table->string('qr_url', 255)->nullable();
            $table->string('kode_bayar', 255)->nullable();
            $table->integer('is_installment')->nullable();
            $table->integer('installment_completed')->nullable();
            $table->dateTime('used_time')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
