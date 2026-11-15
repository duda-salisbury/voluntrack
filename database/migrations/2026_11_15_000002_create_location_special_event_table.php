<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_special_event', function (Blueprint $table) {
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('special_event_id')->constrained()->cascadeOnDelete();
            $table->primary(['location_id', 'special_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_special_event');
    }
};
