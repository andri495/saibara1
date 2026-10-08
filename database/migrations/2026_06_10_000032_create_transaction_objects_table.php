<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_objects', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 255);
            $table->unsignedBigInteger('object_id');
            $table->double('tpj_quantity');
            $table->double('satuan_quantity');
            $table->double('quantity');
            $table->decimal('total_amount', 18,  2);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->text('document_result')->nullable();
            $table->text('document_support_link')->nullable();
            $table->text('document_payment_link')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_objects');
    }
};
