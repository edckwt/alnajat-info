<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** روابط طُلبت وأعادت 404 — لمتابعة فترة ما بعد الانتقال. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missing_links', function (Blueprint $table) {
            $table->id();
            $table->string('path', 1000);
            $table->char('path_hash', 40)->unique();
            $table->string('referer', 1000)->nullable();
            $table->unsignedInteger('hits')->default(1);
            $table->timestamps(); // created_at = أول مرة، updated_at = آخر مرة
            $table->index(['hits', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missing_links');
    }
};
