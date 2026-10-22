<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->foreignId('fund_source_id')->constrained('fund_sources');
            $table->unsignedTinyInteger('month');
            $table->string('status', 20)->default('draf')->index();
            $table->boolean('is_nil')->default(false);
            $table->decimal('opening_fund', 15, 2)->nullable();
            $table->decimal('opening_tax', 15, 2)->nullable();
            $table->decimal('receipt_bos', 15, 2)->default(0);
            $table->decimal('receipt_other', 15, 2)->default(0);
            $table->string('receipt_other_note', 255)->nullable();
            $table->decimal('treasury_deposit', 15, 2)->default(0);
            $table->decimal('tax_withheld', 15, 2)->default(0);
            $table->decimal('tax_remitted', 15, 2)->default(0);
            $table->decimal('bank_balance_bku', 15, 2)->nullable();
            $table->decimal('cash_balance_bku', 15, 2)->nullable();
            $table->text('difference_note')->nullable();
            // Cache untuk dasbor; selalu ditulis ulang oleh ReportBalanceCalculator, bukan sumber kebenaran.
            $table->decimal('computed_ending_fund', 15, 2)->nullable();
            $table->decimal('computed_ending_tax', 15, 2)->nullable();
            $table->decimal('computed_difference', 15, 2)->nullable();
            $table->foreignId('principal_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('principal_approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_late')->default(false);
            $table->boolean('needs_rereview')->default(false);
            $table->json('identity_snapshot')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'fiscal_year_id', 'fund_source_id', 'month'], 'monthly_reports_school_year_fund_month_unique');
            $table->index(['fiscal_year_id', 'month', 'status']);
            $table->index(['school_id', 'fiscal_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
