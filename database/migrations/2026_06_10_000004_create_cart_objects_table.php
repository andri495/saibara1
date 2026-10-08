<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart_objects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('object_id');
            $table->double('quantity_tpj');
            $table->double('quantity_satuan');
            $table->decimal('total_amount', 18,  2);
            $table->text('document_result')->nullable();
            $table->text('document_support_link')->nullable();
            $table->text('document_payment_link')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_objects');
    }
};
