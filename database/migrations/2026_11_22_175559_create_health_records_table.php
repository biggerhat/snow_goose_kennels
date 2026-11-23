<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->morphs('recordable');
            $table->string('type', 20);
            $table->string('name', 150);
            $table->date('given_on');
            $table->date('next_due_on')->nullable();
            $table->string('given_by', 150)->nullable();
            $table->string('lot_number', 60)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('next_due_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
