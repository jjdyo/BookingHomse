<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeslot_preset_trainer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('timeslot_preset_id');
            $table->unsignedBigInteger('trainer_id');
            $table->timestamps();

            $table->unique(['timeslot_preset_id', 'trainer_id']);
            $table->foreign('timeslot_preset_id')->references('id')->on('timeslot_presets')->cascadeOnDelete();
            $table->foreign('trainer_id')->references('id')->on('trainers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeslot_preset_trainer');
    }
};
