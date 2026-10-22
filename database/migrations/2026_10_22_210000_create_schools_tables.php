<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->char('npsn', 8)->unique();
            $table->string('name', 150);
            $table->string('level', 3);
            $table->string('status', 6);
            $table->foreignId('district_id')->constrained('districts');
            $table->string('address', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('role', 20);
            $table->string('name', 150);
            $table->char('nip', 18)->nullable();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'role']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
        });
        Schema::dropIfExists('school_officials');
        Schema::dropIfExists('schools');
    }
};
