<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_tipe_dokumen_id');
            $table->string('transaksi_id', 255);
            $table->unsignedBigInteger('installment_id')->nullable();
            $table->integer('master_chief_id')->nullable();
            $table->string('document_number', 255);
            $table->string('linked_document_number', 255)->nullable();
            $table->dateTime('issue_date')->nullable();
            $table->text('download_link')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
