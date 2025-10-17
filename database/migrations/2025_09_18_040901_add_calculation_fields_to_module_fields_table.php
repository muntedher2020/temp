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
            $table->boolean('is_calculated')->default(false)->after('show_in_forms'); // حقل محسوب
            $table->text('calculation_formula')->nullable()->after('is_calculated'); // معادلة الحساب
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->dropColumn(['is_calculated', 'calculation_formula']);
        });
    }
};
