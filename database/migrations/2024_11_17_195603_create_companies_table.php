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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('description')->nullable();
            $table->string('website')->nullable();
            $table->string('slogan')->nullable();
            $table->unsignedBigInteger('owner')->foreign('owner')->references('id')->on('users');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('national_id')->unique();
            $table->string('address');
            $table->timestamp('established_date')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(0);
            $table->unsignedBigInteger('plan_id')->foreign('plan_id')->references('id')->on('plans')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
