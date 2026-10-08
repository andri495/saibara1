<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_operational_hours', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_operational_rules_id');
            $table->string('day_of_week', 100);
            $table->time('open_time');
            $table->time('close_time');
            $table->integer('is_closed');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_operational_hours');
    }
};
