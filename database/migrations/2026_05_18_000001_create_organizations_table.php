<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            // Routing & identity
            $table->string('name');
            $table->string('slug')->unique(); // used in public URLs: /{slug}/

            // Branding — scalar because they may drive logic or CSS generation
            $table->string('logo_path')->nullable(); // relative path in public storage
            $table->string('accent_color', 7)->default('#0d6efd'); // hex color

            // Lifecycle
            $table->boolean('is_active')->default(true)->index();

            // Display & form configuration — stored as JSON because these values
            // are never queried/filtered; adding new options requires no migration.
            // See App\Support\OrganizationSettings for the canonical shape.
            $table->json('settings')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
