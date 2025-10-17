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
            $table->string('calculation_type')->default('none')->after('calculation_formula')->comment('نوع الحساب: none, formula, date_diff');
            $table->string('date_from_field')->nullable()->after('calculation_type')->comment('الحقل المرجعي للتاريخ من');
            $table->string('date_to_field')->nullable()->after('date_from_field')->comment('الحقل المرجعي للتاريخ إلى');
            $table->string('date_diff_unit')->default('days')->after('date_to_field')->comment('وحدة قياس الفرق: days, months, years');
            $table->boolean('include_end_date')->default(false)->after('date_diff_unit')->comment('شمل التاريخ النهائي في الحساب');
            $table->boolean('absolute_value')->default(false)->after('include_end_date')->comment('قيمة مطلقة للنتيجة');
            $table->boolean('remaining_only')->default(false)->after('absolute_value')->comment('الأيام المتبقية من الشهر أو الأشهر المتبقية من السنة فقط');
            $table->boolean('is_date_calculated')->default(false)->after('remaining_only')->comment('هل الحقل محسوب للتاريخ');
            $table->json('date_calculation_config')->nullable()->after('is_date_calculated')->comment('إعدادات حساب التاريخ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->dropColumn([
                'calculation_type',
                'date_from_field',
                'date_to_field',
                'date_diff_unit',
                'include_end_date',
                'absolute_value',
                'remaining_only',
                'is_date_calculated',
                'date_calculation_config'
            ]);
        });
    }
};
