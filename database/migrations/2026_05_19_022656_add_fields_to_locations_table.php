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
        Schema::table('locations', function (Blueprint $table) {
            // Organization ownership (may already exist from earlier migration run)
            if (! Schema::hasColumn('locations', 'organization_id')) {
                $table->foreignId('organization_id')
                      ->after('id')
                      ->constrained()
                      ->cascadeOnDelete();
            }

            // Slug for URL-friendly references and external import deduplication
            $table->string('slug')->nullable()->after('name');

            // Human-readable description
            if (! Schema::hasColumn('locations', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }

            // Import provenance — where did this record come from?
            // e.g. 'manual', 'trailforks', 'csv'
            $table->string('source')->default('manual')->after('description');

            // Opaque ID from the upstream system for idempotent re-imports
            $table->string('external_id')->nullable()->after('source');

            // Soft on/off toggle without destroying data
            $table->boolean('is_active')->default(true)->after('external_id');

            $table->unique(['organization_id', 'slug'], 'locations_org_slug_unique');
            $table->unique(['organization_id', 'external_id'], 'locations_org_external_unique');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique('locations_org_slug_unique');
            $table->dropUnique('locations_org_external_unique');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['slug', 'source', 'external_id', 'is_active']);
        });
    }
};
