<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Shifts (Templates)
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        // 2. Schedules (The Roster)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->date('date');
            $table->timestamps();

            // Unique constraint: One shift per employee per day
            $table->unique(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('shifts');
    }
};
