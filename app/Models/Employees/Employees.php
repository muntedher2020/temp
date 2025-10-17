<?php
namespace App\Models\Employees;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Employees extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "employees";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // علاقة مع educational_levels
    public function educational_level()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\EducationalLevels\EducationalLevels')) {
            return $this->belongsTo('App\Models\EducationalLevels\EducationalLevels', 'ed_level_id', 'id');
        } elseif (class_exists('App\Models\EducationalLevel\EducationalLevel')) {
            return $this->belongsTo('App\Models\EducationalLevel\EducationalLevel', 'ed_level_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم التحصيل العلمي
    public function getEdLevelIdNameAttribute()
    {
        if ($this->educational_level) {
            return $this->educational_level->name ?? $this->educational_level->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }

    // علاقة مع departments
    public function department()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\Departments\Departments')) {
            return $this->belongsTo('App\Models\Departments\Departments', 'department_id', 'id');
        } elseif (class_exists('App\Models\Department\Department')) {
            return $this->belongsTo('App\Models\Department\Department', 'department_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم القسم
    public function getDepartmentIdNameAttribute()
    {
        if ($this->department) {
            return $this->department->name ?? $this->department->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }

    // علاقة مع job_titles
    public function job_title()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\JobTitles\JobTitles')) {
            return $this->belongsTo('App\Models\JobTitles\JobTitles', 'job_title_id', 'id');
        } elseif (class_exists('App\Models\JobTitle\JobTitle')) {
            return $this->belongsTo('App\Models\JobTitle\JobTitle', 'job_title_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم العنوان الوظيفي
    public function getJobTitleIdNameAttribute()
    {
        if ($this->job_title) {
            return $this->job_title->name ?? $this->job_title->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }

    // علاقة مع job_grades
    public function job_grade()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\JobGrades\JobGrades')) {
            return $this->belongsTo('App\Models\JobGrades\JobGrades', 'job_grade_id', 'id');
        } elseif (class_exists('App\Models\JobGrade\JobGrade')) {
            return $this->belongsTo('App\Models\JobGrade\JobGrade', 'job_grade_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم الدرجة الوظيفية
    public function getJobGradeIdNameAttribute()
    {
        if ($this->job_grade) {
            return $this->job_grade->name ?? $this->job_grade->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }
}