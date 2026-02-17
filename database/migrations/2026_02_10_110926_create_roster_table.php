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
        // 1. Schedules (The Roster)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            $table->enum('day_of_week', [
                'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY',
            ]);

            $table->time('start_time');
            $table->time('end_time');

            $table->timestamps();

            // Unique constraint: An employee cannot have two different schedules for the same day of the week.
            $table->unique(['employee_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
