<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_entries', function (Blueprint $table) {
            // Links an entry to a registered volunteer account.
            // Null means the entry was submitted anonymously or with a name only.
            // The 'name' column on the entry remains the display name regardless.
            $table->foreignId('volunteer_user_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_entries', function (Blueprint $table) {
            $table->dropForeign(['volunteer_user_id']);
            $table->dropColumn('volunteer_user_id');
        });
    }
};
