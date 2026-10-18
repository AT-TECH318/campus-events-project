<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Paths are stored, not the file bytes. One banner per event is enforced
     * when an image is saved, because a unique boolean would also block gallery images.
     */
    public function up(): void
    {
        Schema::create('event_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->boolean('is_banner')->default(false);
            $table->timestamps();

            $table->index(['event_id', 'is_banner']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_images');
    }
};
