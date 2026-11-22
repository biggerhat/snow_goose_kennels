<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dogs', function (Blueprint $table) {
            $table->foreignId('sire_id')->nullable()->constrained('dogs')->nullOnDelete();
            $table->foreignId('dam_id')->nullable()->constrained('dogs')->nullOnDelete();
            $table->boolean('is_ours')->default(true);
            $table->foreignId('owner_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('titles', 200)->nullable();
            $table->string('dna_profile', 60)->nullable();
            $table->date('acquired_on')->nullable();
            $table->date('left_on')->nullable();
            $table->string('left_reason', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sire_id');
            $table->dropConstrainedForeignId('dam_id');
            $table->dropConstrainedForeignId('owner_contact_id');
            $table->dropColumn(['is_ours', 'titles', 'dna_profile', 'acquired_on', 'left_on', 'left_reason']);
        });
    }
};
