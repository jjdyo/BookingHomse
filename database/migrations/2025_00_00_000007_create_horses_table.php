<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('breed');
            $table->boolean('is_bookable')->default(true);
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->integer('cooldown_duration')->nullable();
            $table->string('cooldown_unit')->nullable(); // minutes, hours, days
            $table->timestamps();

            $table->index('name');
            $table->index('is_bookable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horses');
    }
};
