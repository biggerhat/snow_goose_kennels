<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->foreignId('album_id')->nullable()->after('photoable_id')->constrained()->nullOnDelete();
            $table->date('taken_on')->nullable()->after('credit');
            $table->index(['album_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('album_id');
            $table->dropColumn('taken_on');
        });

        Schema::dropIfExists('albums');
    }
};
