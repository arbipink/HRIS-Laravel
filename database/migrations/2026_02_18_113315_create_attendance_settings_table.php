<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('late_fine_amount', 10, 2)->default(50000);
            $table->decimal('absent_fine_amount', 10, 2)->default(100000);
            $table->decimal('no_clock_out_fine_amount', 10, 2)->default(50000);
            $table->integer('grace_period_minutes')->default(30);
            $table->integer('auto_clock_out_grace_hours')->default(12);
            $table->timestamps();
        });

        // Insert default values
        DB::table('attendance_settings')->insert([
            'late_fine_amount' => 50000,
            'absent_fine_amount' => 100000,
            'no_clock_out_fine_amount' => 50000,
            'grace_period_minutes' => 30,
            'auto_clock_out_grace_hours' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
