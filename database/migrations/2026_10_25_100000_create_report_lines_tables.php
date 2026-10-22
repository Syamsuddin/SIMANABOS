<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_expense_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->constrained('monthly_reports')->cascadeOnDelete();
            $table->foreignId('expense_type_id')->constrained('expense_types');
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['monthly_report_id', 'expense_type_id']);
        });

        Schema::create('report_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->constrained('monthly_reports');
            $table->string('kind', 12);
            $table->foreignId('expense_type_id')->nullable()->constrained('expense_types');
            $table->foreignId('origin_fiscal_year_id')->constrained('fiscal_years');
            $table->unsignedTinyInteger('origin_month');
            $table->decimal('amount', 15, 2);
            $table->string('reason', 500);
            $table->timestamps();

            $table->index('monthly_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_adjustments');
        Schema::dropIfExists('report_expense_lines');
    }
};
