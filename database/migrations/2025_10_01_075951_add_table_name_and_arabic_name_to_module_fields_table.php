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
        Schema::table('module_fields', function (Blueprint $table) {
            $table->string('table_name')->nullable()->after('module_name')->comment('اسم الجدول');
            $table->string('module_arabic_name')->nullable()->after('table_name')->comment('الاسم العربي للوحدة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->dropColumn(['table_name', 'module_arabic_name']);
        });
    }
};
