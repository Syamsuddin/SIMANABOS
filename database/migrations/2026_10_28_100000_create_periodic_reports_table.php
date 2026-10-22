<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodic_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->foreignId('fund_source_id')->constrained('fund_sources');
            $table->string('kind', 10); // stage | annual | minutes (rkas hidup di budget_versions)
            // 0 bila bukan tahap, agar unique key efektif (NULL tidak dianggap sama di MySQL).
            $table->unsignedTinyInteger('stage_no')->default(0);
            // Angka pembanding saja: tidak masuk realisasi bulanan maupun dasbor (docs/23 F-09).
            $table->decimal('reported_total_realization', 15, 2)->nullable();
            $table->decimal('reported_ending_balance', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'fiscal_year_id', 'fund_source_id', 'kind', 'stage_no'], 'periodic_reports_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodic_reports');
    }
};
