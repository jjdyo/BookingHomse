<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeslot_preset_horse', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('timeslot_preset_id');
            $table->unsignedBigInteger('horse_id');
            $table->timestamps();

            $table->unique(['timeslot_preset_id', 'horse_id']);
            $table->foreign('timeslot_preset_id')->references('id')->on('timeslot_presets')->cascadeOnDelete();
            $table->foreign('horse_id')->references('id')->on('horses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeslot_preset_horse');
    }
};
