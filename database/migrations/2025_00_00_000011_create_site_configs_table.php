<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_configs', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('Booking Homse');
            // Store opening/closing as time strings (HH:MM:SS) in app timezone
            $table->time('booking_open_time')->default('09:00:00');
            $table->time('booking_close_time')->default('19:00:00');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        // Seed a single row so app can rely on one-row configuration
        \Illuminate\Support\Facades\DB::table('site_configs')->insert([
            'site_name' => 'Booking Homse',
            'booking_open_time' => '09:00:00',
            'booking_close_time' => '19:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_configs');
    }
};
