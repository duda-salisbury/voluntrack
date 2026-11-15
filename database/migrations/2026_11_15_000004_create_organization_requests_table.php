<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 20);
            $table->string('organization_name', 160);
            $table->string('name_hash', 64)->index();
            $table->string('website', 2048)->nullable();
            $table->string('website_domain', 253)->nullable()->index();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('existing_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('pending_name_key', 64)->nullable()->unique();
            $table->string('pending_domain_key', 64)->nullable()->unique();
            $table->string('pending_org_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->index(['requester_id', 'created_at']);
        });

        Schema::create('organization_request_locks', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
        });
        DB::table('organization_request_locks')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_requests');
        Schema::dropIfExists('organization_request_locks');
    }
};
