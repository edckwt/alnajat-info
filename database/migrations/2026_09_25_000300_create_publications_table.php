<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->string('image')->nullable();
            $table->longText('body')->nullable();
            // النشرة تجمع أخبار هذا اليوم (news.published_date).
            $table->date('publication_date')->nullable()->unique();
            $table->integer('cover')->nullable();
            $table->string('other_file')->nullable();
            // نسخة قالب الـ PDF (1..5). تُحسب عند الهجرة من رقم النشرة كما في pdfVersion().
            $table->unsignedTinyInteger('pdf_version')->default(5);
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('legacy_user_id')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'publication_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
