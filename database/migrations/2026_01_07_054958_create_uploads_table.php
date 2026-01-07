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
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('filename');
            $table->string('extension');
            $table->bigInteger('size');
            $table->string('checksum')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('total_chunks');
            $table->integer('uploaded_chunks')->default(0);
            $table->string('path')->nullable(); // Final merged file path
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
