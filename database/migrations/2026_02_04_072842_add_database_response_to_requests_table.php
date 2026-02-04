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
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('response_type', ['manual', 'database'])->default('manual')->after('type');
            $table->string('database_name')->nullable()->after('response_type');
            $table->string('table_name')->nullable()->after('database_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['response_type', 'database_name', 'table_name']);
        });
    }
};
