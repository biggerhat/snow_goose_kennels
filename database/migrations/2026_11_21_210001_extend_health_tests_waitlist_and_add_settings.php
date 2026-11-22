<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_tests', function (Blueprint $table) {
            $table->string('certificate_number')->nullable()->after('result');
            $table->string('verification_url', 500)->nullable()->after('certificate_number');
        });

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->string('source')->default('admin')->after('status');
            $table->json('application')->nullable()->after('notes');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropColumn(['source', 'application']);
        });

        Schema::table('health_tests', function (Blueprint $table) {
            $table->dropColumn(['certificate_number', 'verification_url']);
        });
    }
};
