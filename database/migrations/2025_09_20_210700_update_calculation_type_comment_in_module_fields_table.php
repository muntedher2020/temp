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
            // تحديث تعليق حقل calculation_type ليشمل time_diff
            $table->enum('calculation_type', ['none', 'formula', 'date_diff', 'time_diff'])
                  ->default('none')
                  ->comment('نوع الحساب: none=لا يوجد, formula=معادلة حسابية, date_diff=فرق التواريخ, time_diff=فرق الأوقات')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->string('calculation_type')->default('none')->comment('نوع الحساب: none, formula, date_diff')->change();
        });
    }
};
