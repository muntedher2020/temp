<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('معرف المستخدم');
            $table->string('course_title', 255)->comment('عنوان الدورة');
            $table->string('trainer_id')->comment('اسم المدرب');
            $table->string('domain_id')->comment('المجال التدريبي');
            $table->string('program_manager_id')->comment('مدير البرنامج التدريبي');
            $table->string('venue_id')->comment('مكان انعقاد الدورة');
            $table->string('duration_days', 5)->comment('مدة الدورة');
            $table->string('course_book_no', 50)->comment('رقم كتاب الدورة');
            $table->date('course_book_date')->comment('تاريخ كتاب الدورة');
            $table->string('course_book_image_path')->comment('ملف كتاب الدورة');
            $table->string('postpone_book_no', 50)->nullable()->comment('رقم كتاب التاجيل');
            $table->date('postpone_book_date')->nullable()->comment('تاريخ كتاب التاجيل');
            $table->string('postpone_book_image_path')->nullable()->comment('ملف كتاب التاجيل');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('courses');
    }
};