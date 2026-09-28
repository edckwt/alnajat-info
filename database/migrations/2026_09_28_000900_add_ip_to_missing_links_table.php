<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** عنوان IP لآخر من طلب الرابط المفقود (لمعرفة الزائر من الروبوت، ومتابعة مصدر واحد متكرر). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missing_links', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->after('referer'); // IPv6 حتى 45 حرفاً
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::table('missing_links', function (Blueprint $table) {
            $table->dropIndex(['ip']);
            $table->dropColumn('ip');
        });
    }
};
