<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('معرف المستخدم');
            $table->string('employee_id')->comment('اسم الموظف');
            $table->string('course_id')->comment('عنوان الدورة');
            $table->string('nomination_book_no', 50)->comment('رقم كتاب الترشيح');
            $table->date('nomination_book_date')->comment('تاريخ كتاب الترشيح');
            $table->string('pre_training_level')->nullable()->comment('المستوى قبل التدريب');
            $table->boolean('passed')->default(false)->nullable()->comment('هل اجتاز الدورة');
            $table->string('post_training_level')->nullable()->comment('المستوى بعد التدرب');
            $table->string('attendance_days', 5)->nullable()->comment('عدد ايام الحضور');
            $table->string('absence_days', 5)->nullable()->comment('عدد ايام الغياب');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_candidates');
    }
};