<?php
namespace App\Models\CourseCandidates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CourseCandidates extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "course_candidates";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // علاقة مع employees
    public function employee()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\Employees\Employees')) {
            return $this->belongsTo('App\Models\Employees\Employees', 'employee_id', 'id');
        } elseif (class_exists('App\Models\Employee\Employee')) {
            return $this->belongsTo('App\Models\Employee\Employee', 'employee_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم اسم الموظف
    public function getEmployeeIdNameAttribute()
    {
        if ($this->employee) {
            return $this->employee->employee_name ?? $this->employee->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }

    // علاقة مع courses
    public function course()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\Courses\Courses')) {
            return $this->belongsTo('App\Models\Courses\Courses', 'course_id', 'id');
        } elseif (class_exists('App\Models\Course\Course')) {
            return $this->belongsTo('App\Models\Course\Course', 'course_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم عنوان الدورة
    public function getCourseIdNameAttribute()
    {
        if ($this->course) {
            return $this->course->course_title ?? $this->course->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }
}