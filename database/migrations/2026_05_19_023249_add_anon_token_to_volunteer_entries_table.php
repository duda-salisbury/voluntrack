<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('volunteer_entries', function (Blueprint $table) {
            // Random UUID generated client-side; used to group anonymous
            // submissions from the same browser without any PII.
            // Nullable: entries from registered accounts don't need it.
            $table->uuid('anon_token')
                  ->nullable()
                  ->after('volunteer_user_id')
                  ->index();
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_entries', function (Blueprint $table) {
            $table->dropIndex(['anon_token']);
            $table->dropColumn('anon_token');
        });
    }
};
