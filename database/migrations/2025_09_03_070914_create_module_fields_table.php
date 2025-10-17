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
        Schema::create('module_fields', function (Blueprint $table) {
            $table->id()->comment('معرف الحقل');
            $table->string('module_name')->index()->comment('اسم الوحدة');
            $table->string('field_name')->index()->comment('اسم الحقل');
            $table->string('field_type')->default('text')->index()->comment('نوع الحقل');
            $table->string('arabic_name')->comment('الاسم العربي');
            $table->string('english_name')->nullable()->comment('الاسم الإنجليزي');
            $table->boolean('required')->default(false)->comment('مطلوب');
            $table->boolean('unique')->default(false)->comment('فريد');
            $table->boolean('searchable')->default(true)->comment('قابل للبحث');
            $table->boolean('show_in_table')->default(true)->comment('عرض في الجدول');
            $table->boolean('show_in_search')->default(true)->comment('عرض في البحث');
            $table->boolean('show_in_forms')->default(true)->comment('عرض في النماذج');
            $table->integer('max_length')->nullable()->comment('الطول الأقصى');
            $table->boolean('arabic_only')->default(false)->comment('عربي فقط');
            $table->boolean('numeric_only')->default(false)->comment('رقمي فقط');
            $table->string('file_types')->nullable()->comment('أنواع الملفات');
            $table->longText('select_options')->nullable()->comment('خيارات الاختيار');
            $table->string('select_source')->default('manual')->comment('مصدر الخيارات');
            $table->string('related_table')->nullable()->comment('الجدول المرتبط');
            $table->string('related_key')->default('id')->comment('المفتاح المرتبط');
            $table->string('related_display')->default('name')->comment('الحقل المعروض');
            $table->string('validation_rules')->nullable()->comment('قواعد التحقق');
            $table->longText('validation_messages')->nullable()->comment('رسائل التحقق');
            $table->longText('custom_attributes')->nullable()->comment('خصائص مخصصة');
            $table->string('created_by')->default('generator')->comment('أنشأ بواسطة');
            $table->integer('order')->default(0)->comment('الترتيب');
            $table->boolean('active')->default(true)->comment('نشط');
            $table->timestamps();

            // Indexes
            $table->unique(['module_name', 'field_name']);
            $table->index(['module_name', 'active']);
            $table->index(['field_type', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_fields');
    }
};
