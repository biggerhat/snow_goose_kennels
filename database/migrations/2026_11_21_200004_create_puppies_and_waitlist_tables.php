<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puppies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('litter_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('collar')->nullable();
            $table->string('sex');
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('birth_weight_g')->nullable();
            $table->string('status')->default('available');
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sale_price')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('litter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('puppy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('preferred_sex')->nullable();
            $table->string('preferred_color')->nullable();
            $table->unsignedSmallInteger('pick_order')->nullable();
            $table->string('status')->default('waiting');
            $table->unsignedInteger('deposit_amount')->nullable();
            $table->date('deposit_paid_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('puppies');
    }
};
