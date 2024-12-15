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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('text');
            $table->unsignedBigInteger('type')->foreign('type')->references('id')->on('message_types');
            $table->unsignedBigInteger('company_id')->foreign('company_id')->references('id')->on('companies');
            $table->enum('status', ['pending', 'accept', 'reject'])->default('pending');
            $table->boolean('is_active')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
