<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('timeslot_id')->constrained('timeslots')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // client/customer
            $table->foreignId('horse_id')->nullable()->constrained('horses')->nullOnDelete();

            // Lifecycle status
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending')->index();

            // Payments (minimal for now)
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid')->index();
            $table->timestampTz('paid_at')->nullable();

            // Cancellation metadata
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();

            $table->timestamps();

            // Prevent the same user from booking the same timeslot twice
            $table->unique(['timeslot_id', 'user_id']);

            // Prevent the same horse being assigned to more than one booking within the same timeslot
            $table->unique(['timeslot_id', 'horse_id']);

            // Helpful indexes
            $table->index(['timeslot_id']);
            $table->index(['user_id']);
            $table->index(['horse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
