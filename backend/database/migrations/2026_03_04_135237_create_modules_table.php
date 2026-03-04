<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('name');
            $table->enum('status', ['draft', 'validated', 'ready_for_build'])->default('draft');
            
            // The 10 required fields
            $table->text('objective')->nullable();
            $table->json('inputs')->nullable();
            $table->text('data_structure')->nullable();
            $table->text('logic_rules')->nullable();
            $table->json('outputs')->nullable();
            $table->text('responsibility')->nullable();
            $table->text('failure_scenarios')->nullable();
            $table->text('audit_trail_requirements')->nullable();
            $table->json('dependencies')->nullable();
            $table->string('version_note')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};

