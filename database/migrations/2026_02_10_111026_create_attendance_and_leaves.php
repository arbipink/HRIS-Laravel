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
        // 1. Attendances
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->onDelete('set null');

            $table->date('date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();

            $table->enum('status', ['PRESENT', 'LATE', 'ABSENT', 'SICK', 'LEAVE', 'EARLY_LEAVE'])->default('ABSENT');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });

        // 2. Leave Requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            $table->enum('type', [
                'ANNUAL', 'MATERNITY', 'MISCARRIAGE', 'MENSTRUAL',
                'SICK', 'MARRIAGE', 'PATERNITY', 'BEREAVEMENT',
            ])->default('ANNUAL');

            $table->string('reason');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('attachment_path')->nullable();

            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');

            $table->enum('manager_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->text('manager_reason')->nullable();

            $table->enum('hrd_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('hrd_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->text('hrd_reason')->nullable();

            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date')->unique();
            $table->timestamps();
        });

        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fines');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendances');
    }
};
