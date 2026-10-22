<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only: tanpa updated_at, tidak pernah diubah atau dihapus.
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->constrained('monthly_reports');
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->foreignId('actor_id')->constrained('users');
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('review_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->constrained('monthly_reports');
            $table->foreignId('reviewer_id')->constrained('users');
            $table->string('target_type', 15);
            $table->string('target_key', 50)->nullable();
            $table->text('body');
            $table->text('response_body')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('review_notes');
        Schema::dropIfExists('status_histories');
    }
};
