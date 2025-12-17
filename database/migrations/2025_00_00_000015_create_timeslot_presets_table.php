<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeslot_presets', function (Blueprint $table) {
            $table->id();
            $table->string('preset_title');
            $table->text('preset_description')->nullable();

            // Timeslot-like fields (no start/end times)
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            // Some DBs (like SQLite) can be strict with NOT NULL + no value passed for booleans.
            // Ensure a concrete default and allow null values coming from forms to coerce to 0.
            $table->boolean('is_group')->default(false);
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedBigInteger('trainer_id')->nullable();
            $table->string('trainer_name')->nullable();
            $table->string('service_name')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('color', 9)->nullable();

            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeslot_presets');
    }
};
