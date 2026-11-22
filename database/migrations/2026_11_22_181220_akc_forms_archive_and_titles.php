<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dogs', function (Blueprint $table) {
            $table->string('co_owner_name', 150)->nullable();
            $table->softDeletes();
        });

        Schema::table('litters', function (Blueprint $table) {
            $table->date('sire_owner_signed_on')->nullable();
            $table->date('dam_owner_signed_on')->nullable();
            $table->boolean('multiple_sires')->default(false);
            $table->date('akc_marketplace_listed_on')->nullable();
            $table->softDeletes();
        });

        Schema::table('puppies', function (Blueprint $table) {
            $table->date('reunite_enrolled_on')->nullable();
        });

        Schema::create('kennel_profiles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('dog_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dog_id')->constrained()->cascadeOnDelete();
            $table->string('title', 40);
            $table->string('full_name', 150)->nullable();
            $table->string('kind', 20)->default('other');
            $table->date('earned_on')->nullable();
            $table->string('event', 200)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dog_achievements');
        Schema::dropIfExists('kennel_profiles');
        Schema::table('puppies', fn (Blueprint $t) => $t->dropColumn('reunite_enrolled_on'));
        Schema::table('litters', function (Blueprint $t) {
            $t->dropSoftDeletes();
            $t->dropColumn(['sire_owner_signed_on', 'dam_owner_signed_on', 'multiple_sires', 'akc_marketplace_listed_on']);
        });
        Schema::table('dogs', function (Blueprint $t) {
            $t->dropSoftDeletes();
            $t->dropColumn('co_owner_name');
        });
    }
};
