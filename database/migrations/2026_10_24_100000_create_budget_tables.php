<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->foreignId('fund_source_id')->constrained('fund_sources');
            $table->decimal('amount', 15, 2);
            $table->unsignedTinyInteger('active_from_month')->default(1);
            $table->timestamps();

            $table->unique(['school_id', 'fiscal_year_id', 'fund_source_id'], 'allocations_school_year_fund_unique');
        });

        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->foreignId('fund_source_id')->constrained('fund_sources');
            $table->decimal('fund_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->string('origin', 15)->default('manual');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'fiscal_year_id', 'fund_source_id'], 'opening_balances_school_year_fund_unique');
        });

        Schema::create('budget_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->foreignId('fund_source_id')->constrained('fund_sources');
            $table->unsignedSmallInteger('version_no');
            $table->date('effective_date');
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'fiscal_year_id', 'fund_source_id', 'version_no'], 'budget_versions_school_year_fund_no_unique');
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_version_id')->constrained('budget_versions')->cascadeOnDelete();
            $table->foreignId('expense_type_id')->constrained('expense_types');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['budget_version_id', 'expense_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budget_versions');
        Schema::dropIfExists('opening_balances');
        Schema::dropIfExists('allocations');
    }
};
