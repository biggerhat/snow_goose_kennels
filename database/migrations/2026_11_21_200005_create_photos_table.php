<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('photoable');
            $table->string('path');
            $table->string('thumb_path');
            $table->string('caption')->nullable();
            $table->string('credit')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('in_gallery')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
