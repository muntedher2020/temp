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
        Schema::create('basic_groups', function (Blueprint $table) {
            $table->id()->comment('معرف المجموعة الأساسية');
            $table->string('name_en')->comment('الاسم بالإنجليزية');
            $table->string('name_ar')->comment('الاسم بالعربية');
            $table->string('icon')->default('mdi mdi-folder-outline')->comment('أيقونة المجموعة');
            $table->string('description_en')->nullable()->comment('الوصف بالإنجليزية');
            $table->string('description_ar')->nullable()->comment('الوصف بالعربية');
            $table->integer('sort_order')->default(0)->comment('ترتيب العرض');
            $table->boolean('status')->default(true)->comment('حالة التفعيل');
            $table->softDeletes()->comment('تاريخ الحذف المؤقت');
            $table->timestamps();

            // إضافة فهارس للبحث السريع
            $table->index(['status', 'sort_order']);
            $table->index('name_ar');
            $table->index('name_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basic_groups');
    }
};
