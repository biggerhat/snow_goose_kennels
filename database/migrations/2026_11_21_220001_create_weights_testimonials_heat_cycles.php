<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puppy_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puppy_id')->constrained()->cascadeOnDelete();
            $table->date('weighed_on');
            $table->unsignedInteger('weight_g');
            $table->timestamps();
            $table->unique(['puppy_id', 'weighed_on']);
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('puppy_name')->nullable();
            $table->text('body');
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('heat_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dog_id')->constrained()->cascadeOnDelete();
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->date('bred_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heat_cycles');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('puppy_weights');
    }
};
