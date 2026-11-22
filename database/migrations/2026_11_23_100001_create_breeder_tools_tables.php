<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('litter_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('litter_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30)->default('custom');
            $table->string('title', 150);
            $table->string('detail', 400)->nullable();
            $table->date('due_on');
            $table->date('done_on')->nullable();
            $table->timestamps();
            $table->index(['litter_id', 'due_on']);
        });

        Schema::table('puppies', function (Blueprint $table) {
            $table->string('born_time', 5)->nullable();
            $table->unsignedSmallInteger('birth_order')->nullable();
            $table->string('birth_notes', 500)->nullable();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on');
            $table->string('category', 30);
            $table->unsignedInteger('amount_cents');
            $table->string('vendor', 150)->nullable();
            $table->string('description', 300)->nullable();
            $table->foreignId('litter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dog_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('spent_on');
        });

        Schema::create('litter_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('litter_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 200);
            $table->text('body');
            $table->unsignedSmallInteger('recipient_count')->default(0);
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litter_messages');
        Schema::dropIfExists('expenses');
        Schema::table('puppies', fn (Blueprint $t) => $t->dropColumn(['born_time', 'birth_order', 'birth_notes']));
        Schema::dropIfExists('litter_tasks');
    }
};
