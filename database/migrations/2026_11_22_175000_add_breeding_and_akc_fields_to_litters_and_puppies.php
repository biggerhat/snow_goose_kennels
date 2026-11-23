<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('litters', function (Blueprint $table) {
            $table->string('breeding_method', 20)->nullable();
            $table->string('bred_place', 150)->nullable();
            $table->string('bred_handler', 150)->nullable();
            $table->unsignedInteger('stud_fee')->nullable();
            $table->date('stud_fee_paid_on')->nullable();
            $table->unsignedTinyInteger('stillborn_count')->nullable();
            $table->date('akc_applied_on')->nullable();
            $table->string('akc_litter_number', 40)->nullable();
        });

        Schema::table('puppies', function (Blueprint $table) {
            $table->string('microchip', 20)->nullable();
            $table->string('registration_type', 10)->nullable();
            $table->string('akc_number', 40)->nullable();
            $table->date('papers_given_on')->nullable();
            $table->date('went_home_on')->nullable();
            $table->date('vet_checked_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('litters', function (Blueprint $table) {
            $table->dropColumn(['breeding_method', 'bred_place', 'bred_handler', 'stud_fee', 'stud_fee_paid_on', 'stillborn_count', 'akc_applied_on', 'akc_litter_number']);
        });

        Schema::table('puppies', function (Blueprint $table) {
            $table->dropColumn(['microchip', 'registration_type', 'akc_number', 'papers_given_on', 'went_home_on', 'vet_checked_on']);
        });
    }
};
