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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('message_text');
            $table->unsignedBigInteger('from')->foreign('from')->references('id')->on('companies');
            $table->unsignedBigInteger('to')->foreign('to')->references('id')->on('users');
            $table->enum('status', ['sent', 'received', 'failed'])->nullable();
            $table->enum('type', ['auth', 'notification', 'advertise']);
            $table->enum('platform', ['app', 'telegram']);
            $table->boolean('is_read')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
