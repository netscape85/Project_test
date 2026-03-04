<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users')->onDelete('cascade');
            $table->string('entity_type'); // project, artifact, module
            $table->unsignedBigInteger('entity_id');
            $table->string('action'); // created, updated, status_changed, validated, completed
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};

