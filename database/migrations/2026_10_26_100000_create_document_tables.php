<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('scope', 10);
            $table->timestamps();
        });

        // fund_source_id NULL = semua sumber dana; keunikan baris NULL dijaga DocumentRequirementRequest.
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->string('report_kind', 10);
            $table->string('school_status', 10);
            $table->foreignId('fund_source_id')->nullable()->constrained('fund_sources');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'document_type_id', 'report_kind', 'school_status', 'fund_source_id'], 'document_requirements_unique');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents');
            $table->unsignedSmallInteger('version_no');
            $table->string('storage_path', 255);
            $table->string('original_name', 255);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('replaced_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version_no']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('document_versions')->nullOnDelete();
        });

        Schema::create('document_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->timestamps();

            $table->unique(['document_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_tags');
        Schema::table('documents', fn (Blueprint $table) => $table->dropForeign(['current_version_id']));
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_requirements');
        Schema::dropIfExists('document_types');
    }
};
