<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قوالب النشرة المصمَّمة بالسحب والإفلات (الغلاف، الصفحة المتكررة، الختام).
 * القوالب القديمة (css/pdf-v1..v5) تبقى كما هي عبر publications.pdf_version؛
 * النشرة التي لها pdf_template_id تُرسم بالقالب المصمَّم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            // draft = قابل للتعديل، published = معتمد (لا يُعدّل؛ يُنسخ)
            $table->string('status', 20)->default('draft');
            $table->boolean('is_default')->default(false);
            $table->json('design');
            $table->foreignId('based_on_id')->nullable()->constrained('pdf_templates')->nullOnDelete();
            $table->unsignedTinyInteger('based_on_version')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->foreignId('pdf_template_id')->nullable()->after('pdf_version')->constrained('pdf_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('publications', fn (Blueprint $table) => $table->dropConstrainedForeignId('pdf_template_id'));
        Schema::dropIfExists('pdf_templates');
    }
};
