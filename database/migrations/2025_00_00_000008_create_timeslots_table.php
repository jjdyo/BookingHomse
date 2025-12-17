<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeslots', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->dateTimeTz('start_at');
            $table->dateTimeTz('end_at');

            // Capacity / grouping
            $table->unsignedInteger('capacity')->default(1);
            $table->boolean('is_group')->default(false);

            // Pricing per seat
            $table->decimal('price', 10, 2)->default(0);

            // Ownership & trainer attribution
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trainer_name')->nullable(); // Optional display name if not tying to a user

            // Optional service name/category to allow concurrent different services
            $table->string('service_name')->nullable();

            // Location
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            // Color
            $table->string('color', 9)->nullable()->after('location_id');

            // Google Calendar sync meta
            $table->string('google_event_id')->nullable()->index();
            $table->enum('sync_status', ['pending', 'synced', 'error'])->default('pending');
            $table->timestampTz('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index(['start_at']);
            $table->index(['end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeslots');
    }
};
