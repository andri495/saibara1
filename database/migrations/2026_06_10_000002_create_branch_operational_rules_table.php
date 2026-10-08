<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_operational_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('master_lokasi_id');
            $table->string('rule_name', 255);
            $table->string('is_active');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_operational_rules');
    }
};
