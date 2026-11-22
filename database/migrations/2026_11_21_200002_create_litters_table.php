<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('litters', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name')->nullable();
            $table->foreignId('sire_id')->nullable()->constrained('dogs')->nullOnDelete();
            $table->foreignId('dam_id')->nullable()->constrained('dogs')->nullOnDelete();
            $table->string('status')->default('planned');
            $table->date('bred_on')->nullable();
            $table->date('due_on')->nullable();
            $table->date('born_on')->nullable();
            $table->date('go_home_on')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->unsignedInteger('deposit')->nullable();
            $table->unsignedSmallInteger('expected_count')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->text('private_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litters');
    }
};
