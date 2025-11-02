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
        Schema::create('user_databases', function (Blueprint $table) {
            $table->id(); // primary key
            $table->unsignedBigInteger('user_id'); // reference to users table
            $table->string('database_name', 255);
            $table->timestamps();

            // Optional: add foreign key if you have users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_databases');
    }
};
