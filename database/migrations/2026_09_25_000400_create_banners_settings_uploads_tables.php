<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('url')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('clicks')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
        });

        // صناديق الصفحة الرئيسية (home) وصناديق النشرة (pdf).
        // كانت 150 مفتاحاً في جدول setting: box_{category,limit,type,banner,code}_{1..15}.
        Schema::create('home_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('context', 10); // home | pdf
            $table->unsignedTinyInteger('position');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('items_limit')->default(0);
            $table->unsignedTinyInteger('type')->default(0);
            $table->foreignId('banner_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('code')->nullable();
            $table->timestamps();

            $table->unique(['context', 'position']);
        });

        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('extension', 20)->nullable();
            $table->string('mime', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
        Schema::dropIfExists('home_boxes');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('banners');
    }
};
