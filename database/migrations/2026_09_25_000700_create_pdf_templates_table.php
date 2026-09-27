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

        // على SQLite (الاختبارات) إضافة قيد مفتاح أجنبي لجدول موجود تعني إعادة بناء الجدول، وLaravel يقرأ أعمدته
        // بـ pragma_table_xinfo() التي لا تدعمها نسخ SQLite القديمة في بعض توزيعات PHP (مثل MAMP):
        // «near "(": syntax error». فيُضاف العمود وحده هناك، والقيد على MySQL كما هو. الحذف محمي في
        // PdfTemplateController::destroy (لا يُحذف قالب مستخدم)، فلا يتغير السلوك.
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('publications', function (Blueprint $table) use ($sqlite) {
            $column = $table->foreignId('pdf_template_id')->nullable()->after('pdf_version');
            if (! $sqlite) {
                $column->constrained('pdf_templates')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            Schema::getConnection()->getDriverName() === 'sqlite'
                ? $table->dropColumn('pdf_template_id')
                : $table->dropConstrainedForeignId('pdf_template_id');
        });
        Schema::dropIfExists('pdf_templates');
    }
};
