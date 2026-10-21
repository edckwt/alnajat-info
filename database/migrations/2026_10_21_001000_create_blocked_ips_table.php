<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** العناوين المحظورة (App\Support\Firewall): صف لكل عنوان، يبقى بعد انتهاء الحظر ليتصاعد عند التكرار. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->string('reason', 20);                       // trap | flood | manual
            $table->string('trigger', 1000)->nullable();         // الرابط الذي كشفه، أو ملاحظة الحظر اليدوي
            $table->unsignedSmallInteger('strikes')->default(1); // مرات الحظر (للتصاعد)
            $table->unsignedInteger('hits')->default(0);         // الطلبات المرفوضة أثناء الحظر
            $table->timestamp('blocked_until')->nullable();      // null = دائم
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('blocked_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
