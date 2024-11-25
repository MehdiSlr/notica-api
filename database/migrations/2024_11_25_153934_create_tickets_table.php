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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->foreign('company_id')->references('id')->on('companies');
            $table->unsignedBigInteger('user_id')->foreign('user_id')->references('id')->on('users');
            $table->string('subject');
            $table->string('body');
            $table->unsignedBigInteger('reply_id')->nullable()->foreign('reply_id')->references('id')->on('tickets');
            $table->string('file')->nullable();
            $table->enum('status', ['pending', 'checked', 'closed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
