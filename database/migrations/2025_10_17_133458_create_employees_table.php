<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('معرف المستخدم');
            $table->string('employee_name', 255)->unique()->comment('اسم الموظف');
            $table->string('gender')->comment('الجنس');
            $table->string('ed_level_id')->comment('التحصيل العلمي');
            $table->string('department_id')->comment('القسم');
            $table->string('job_title_id')->comment('العنوان الوظيفي');
            $table->string('job_grade_id')->comment('الدرجة الوظيفية');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};