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
        Schema::create('report_generators', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('عنوان التقرير');
            $table->string('module_name')->comment('اسم الوحدة');
            $table->string('table_name')->comment('اسم الجدول');
            $table->json('selected_columns')->nullable()->comment('الأعمدة المحددة للعرض');
            $table->json('filter_columns')->nullable()->comment('الأعمدة المتاحة للفلترة');
            $table->json('filter_values')->nullable()->comment('قيم الفلاتر المحددة');
            $table->json('chart_settings')->nullable()->comment('إعدادات المخططات البيانية');
            $table->string('sort_column')->nullable()->comment('عمود الترتيب');
            $table->enum('sort_direction', ['asc', 'desc'])->default('asc')->comment('اتجاه الترتيب');
            $table->boolean('is_public')->default(false)->comment('هل التقرير عام');
            $table->text('description')->nullable()->comment('وصف التقرير');
            $table->unsignedBigInteger('created_by')->comment('منشئ التقرير');
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active')->comment('حالة التقرير');
            $table->timestamps();
            $table->softDeletes();

            // الفهارس والعلاقات
            $table->index(['module_name', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['is_public', 'status']);

            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_generators');
    }
};
