<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dogs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('registered_name')->nullable();
            $table->string('sex');
            $table->string('role')->default('other');
            $table->string('color')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_public')->default(true);
            $table->string('registry_number')->nullable();
            $table->string('microchip')->nullable();
            $table->text('private_notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('health_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dog_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('result');
            $table->date('tested_on')->nullable();
            $table->boolean('is_public')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_tests');
        Schema::dropIfExists('dogs');
    }
};
