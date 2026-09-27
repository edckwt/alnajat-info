<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الأدوار وصلاحياتها. users.role يبقى كما هو (مفتاح الدور: admin، editor، ...)
 * فلا يتغير شيء للأعضاء الحاليين، ويُضاف لكل عضو صلاحيات إضافية اختيارية.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('role');
        });

        $now = now();
        foreach (config('permissions.system_roles') as $key => $role) {
            DB::table('roles')->insert([
                'key' => $key,
                'name' => $role['name'],
                'description' => $role['description'],
                'permissions' => json_encode($role['permissions']),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('permissions'));
        Schema::dropIfExists('roles');
    }
};
