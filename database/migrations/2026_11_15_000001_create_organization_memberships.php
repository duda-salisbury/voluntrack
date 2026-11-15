<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false);
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();
            $table->primary(['organization_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        DB::table('users')->where('is_admin', true)->whereNull('organization_id')
            ->update(['is_platform_admin' => true]);
        DB::table('users')->whereNotNull('organization_id')->orderBy('id')->chunkById(500, function ($users) {
            $memberships = $users->map(fn ($user) => [
                'organization_id' => $user->organization_id,
                'user_id' => $user->id,
                'role' => $user->is_admin ? 'admin' : 'volunteer',
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();
            DB::table('organization_user')->insert($memberships);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_platform_admin'));
    }
};
