<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('source_url')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('newspaper_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('newspaper_number')->default(0);
            $table->date('published_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('hide_in_pdf')->default(false);
            $table->boolean('hide_title')->default(false);
            $table->boolean('hide_description')->default(false);
            $table->boolean('hide_more')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('pdf_views')->default(0);
            $table->text('tweet_url')->nullable();
            $table->text('sound_url')->nullable();
            $table->text('video_url')->nullable();
            $table->boolean('original_image')->default(false);
            $table->unsignedTinyInteger('type')->default(0);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            // الكاتب في النظام القديم حين يكون حسابه قد حُذف (للأرشيف فقط).
            $table->unsignedInteger('legacy_user_id')->nullable();
            $table->timestamps();

            // أخبار اليوم والـ PDF: فلترة بالتاريخ ثم ترتيب يدوي.
            $table->index(['is_active', 'published_date', 'sort_order']);
        });

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('news', function (Blueprint $table) {
                $table->fullText(['title', 'description', 'body']);
            });
        }

        Schema::create('category_news', function (Blueprint $table) {
            $table->foreignId('news_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['news_id', 'category_id']);
            $table->index(['category_id', 'news_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_news');
        Schema::dropIfExists('news');
    }
};
