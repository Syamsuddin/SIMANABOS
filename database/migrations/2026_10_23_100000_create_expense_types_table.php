<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('arkas_account_code', 50)->nullable();
            $table->string('name', 150);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_types');
    }
};
