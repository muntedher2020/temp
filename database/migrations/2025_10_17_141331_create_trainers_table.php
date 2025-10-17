<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('معرف المستخدم');
            $table->string('trainer_name', 255)->unique()->comment('اسم المدرب');
            $table->string('institution_id')->comment('مؤسسة المدرب');
            $table->string('ed_level_id')->comment('التحصيل العلمي');
            $table->string('domain_id')->comment('المجال التدريبي');
            $table->string('phone', 50)->nullable()->comment('رقم الهاتف');
            $table->string('email')->nullable()->comment('البريد الالكتروني');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trainers');
    }
};