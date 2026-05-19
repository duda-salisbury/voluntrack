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
        Schema::create('volunteer_entries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->date('volunteered_at');
            $table->decimal('hours', 5, 2);
            $table->string('source')->nullable(); // 'manual', 'trailforks', 'eventbrite', etc.
            $table->boolean('is_matched')->default(false);
            $table->foreignId('matched_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('volunteered_at');
            $table->index('is_matched');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_entries');
    }
};
