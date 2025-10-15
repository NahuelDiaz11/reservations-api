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
        Schema::create('reservations', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('created_by');
        $table->text('address');
        $table->decimal('lat', 10, 7);
        $table->decimal('lng', 10, 7);
        $table->enum('state', ['RESERVED', 'SCHEDULED', 'INSTALLED', 'UNINSTALLED', 'CANCELED'])->default('RESERVED');
        $table->timestamps();
        
        $table->index(['state']);
        $table->index(['created_by']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
