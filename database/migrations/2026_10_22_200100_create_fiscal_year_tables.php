<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('status', 10)->default('open');
            $table->unsignedTinyInteger('default_deadline_day')->default(10);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fiscal_year_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_no');
            $table->unsignedTinyInteger('start_month');
            $table->unsignedTinyInteger('end_month');
            $table->string('label', 50);
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'stage_no']);
        });

        Schema::create('monthly_deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_deadlines');
        Schema::dropIfExists('fiscal_year_stages');
        Schema::dropIfExists('fiscal_years');
    }
};
