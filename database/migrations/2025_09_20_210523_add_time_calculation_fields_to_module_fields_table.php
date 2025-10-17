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
            $table->string('time_from_field')->nullable()->after('date_calculation_config')->comment('الحقل المرجعي للوقت من');
            $table->string('time_to_field')->nullable()->after('time_from_field')->comment('الحقل المرجعي للوقت إلى');
            $table->string('time_diff_unit')->default('minutes')->after('time_to_field')->comment('وحدة قياس فرق الوقت: minutes, hours');
            $table->boolean('is_time_calculated')->default(false)->after('time_diff_unit')->comment('هل الحقل محسوب للوقت');
            $table->json('time_calculation_config')->nullable()->after('is_time_calculated')->comment('إعدادات حساب الوقت');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_fields', function (Blueprint $table) {
            $table->dropColumn([
                'time_from_field',
                'time_to_field',
                'time_diff_unit',
                'is_time_calculated',
                'time_calculation_config'
            ]);
        });
    }
};
