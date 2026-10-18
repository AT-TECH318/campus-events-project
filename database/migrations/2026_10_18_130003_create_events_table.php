<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * category_id and organizer_id use restrict so a category or organizer
     * cannot be removed while events still depend on them.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('organizer_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('venue');
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('maximum_capacity');
            $table->dateTime('registration_deadline');
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index('title');
            $table->index('event_date');
            $table->index(['status', 'event_date']);
            $table->index('registration_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
