<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('travel_routes', function (Blueprint $table) {
            $table->string('start_name')->nullable();
            $table->decimal('start_lat', 9, 6)->nullable();
            $table->decimal('start_lng', 9, 6)->nullable();
            $table->string('end_name')->nullable();
            $table->decimal('end_lat', 9, 6)->nullable();
            $table->decimal('end_lng', 9, 6)->nullable();
        });

        Schema::table('places', function (Blueprint $table) {
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_routes', function (Blueprint $table) {
            $table->dropColumn(['start_name', 'start_lat', 'start_lng', 'end_name', 'end_lat', 'end_lng']);
        });

        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
