<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_template_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id')->comment('معرف القالب');
            $table->unsignedBigInteger('user_id')->nullable()->comment('معرف المستخدم');
            $table->enum('operation_type', ['export', 'import'])->comment('نوع العملية');
            $table->integer('records_count')->default(0)->comment('عدد السجلات المعالجة');
            $table->string('file_name')->nullable()->comment('اسم الملف المُنتج أو المستورد');
            $table->string('file_format')->nullable()->comment('نوع الملف');
            $table->decimal('file_size_mb', 10, 2)->nullable()->comment('حجم الملف بالميجابايت');
            $table->integer('execution_time_seconds')->nullable()->comment('وقت التنفيذ بالثواني');
            $table->enum('status', ['success', 'failed', 'partial'])->default('success')->comment('حالة العملية');
            $table->json('error_details')->nullable()->comment('تفاصيل الأخطاء في حالة الفشل');
            $table->json('operation_metadata')->nullable()->comment('بيانات إضافية عن العملية');
            $table->timestamp('started_at')->nullable()->comment('وقت بداية العملية');
            $table->timestamp('completed_at')->nullable()->comment('وقت انتهاء العملية');
            $table->timestamps();

            // Indexes
            $table->index(['template_id', 'operation_type']);
            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['started_at']);

            // Foreign Keys
            $table->foreign('template_id')->references('id')->on('data_templates')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_template_usages');
    }
};
