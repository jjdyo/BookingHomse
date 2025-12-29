<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_bookable')->default(true);
            $table->timestamps();

            $table->index('name');
            $table->index('is_bookable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
