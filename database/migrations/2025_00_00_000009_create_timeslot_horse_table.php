<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeslot_horse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeslot_id')->constrained('timeslots')->cascadeOnDelete();
            $table->foreignId('horse_id')->constrained('horses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['timeslot_id', 'horse_id']);
            $table->index(['horse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeslot_horse');
    }
};
