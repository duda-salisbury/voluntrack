<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_entries', function (Blueprint $table) {
            $table->decimal('hours', 7, 2)->change();
            $table->boolean('is_group_total')->default(false);
            $table->unsignedInteger('participant_count')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('volunteer_entries')->where('is_group_total', true)->exists()) {
            throw new RuntimeException('Cannot remove group-total fields while group total records exist.');
        }
        if (DB::table('volunteer_entries')->where('hours', '>', 999.99)->exists()) {
            throw new RuntimeException('Cannot shrink volunteer hours while entries exceed 999.99 hours.');
        }
        Schema::table('volunteer_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_admin_id');
            $table->dropColumn(['is_group_total', 'participant_count']);
            $table->decimal('hours', 5, 2)->change();
        });
    }
};
