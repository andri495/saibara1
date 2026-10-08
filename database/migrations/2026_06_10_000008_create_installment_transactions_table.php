<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('installment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('installment_ref_id', 255)->nullable();
            $table->string('installment_old_ref_id', 255)->nullable();
            $table->string('transaction_id', 255)->nullable();
            $table->integer('installment_total')->nullable();
            $table->integer('installment_index')->nullable();
            $table->decimal('payment_amount', 18,  2)->nullable();
            $table->decimal('penalty_amount', 12,  2)->nullable();
            $table->decimal('grand_total', 18,  2)->nullable();
            $table->integer('master_tipe_pembayaran_id')->nullable();
            $table->date('next_penalty_date')->nullable();
            $table->integer('master_status_pembayaran_id')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('paid_date')->nullable();
            $table->text('qr_url')->nullable();
            $table->string('va', 255)->nullable();
            $table->string('kode_bayar', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_transactions');
    }
};
