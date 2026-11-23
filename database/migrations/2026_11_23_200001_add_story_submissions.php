<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('email')->nullable()->after('puppy_name');
            // A story a visitor sent in stays hidden until the owner approves it.
            $table->boolean('needs_review')->default(false)->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['email', 'needs_review']);
        });
    }
};
