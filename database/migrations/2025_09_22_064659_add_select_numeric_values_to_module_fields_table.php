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
            $table->boolean('select_numeric_values')->default(false)->after('select_options')->comment('الحقل المرجعي للقائمة المنسدلة يحتوي على قيم رقمية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->dropColumn('select_numeric_values');
        });
    }
};
