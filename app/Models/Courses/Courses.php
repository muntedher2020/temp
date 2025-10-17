<?php
namespace App\Models\Courses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Courses extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "courses";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // علاقة مع trainers
    public function trainer()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\Trainers\Trainers')) {
            return $this->belongsTo('App\Models\Trainers\Trainers', 'trainer_id', 'id');
        } elseif (class_exists('App\Models\Trainer\Trainer')) {
            return $this->belongsTo('App\Models\Trainer\Trainer', 'trainer_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم اسم المدرب
    public function getTrainerIdNameAttribute()
    {
        if ($this->trainer) {
            return $this->trainer->trainer_name ?? $this->trainer->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }

    // علاقة مع training_domains
    public function training_domain()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\TrainingDomains\TrainingDomains')) {
            return $this->belongsTo('App\Models\TrainingDomains\TrainingDomains', 'domain_id', 'id');
        } elseif (class_exists('App\Models\TrainingDomain\TrainingDomain')) {
            return $this->belongsTo('App\Models\TrainingDomain\TrainingDomain', 'domain_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم المجال التدريبي
    public function getDomainIdNameAttribute()
    {
        if ($this->training_domain) {
            return $this->training_domain->name ?? $this->training_domain->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }

    // علاقة مع employees
    public function employee()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\Employees\Employees')) {
            return $this->belongsTo('App\Models\Employees\Employees', 'program_manager_id', 'id');
        } elseif (class_exists('App\Models\Employee\Employee')) {
            return $this->belongsTo('App\Models\Employee\Employee', 'program_manager_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم مدير البرنامج التدريبي
    public function getProgramManagerIdNameAttribute()
    {
        if ($this->employee) {
            return $this->employee->employee_name ?? $this->employee->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }

    // علاقة مع venues
    public function venue()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\Venues\Venues')) {
            return $this->belongsTo('App\Models\Venues\Venues', 'venue_id', 'id');
        } elseif (class_exists('App\Models\Venue\Venue')) {
            return $this->belongsTo('App\Models\Venue\Venue', 'venue_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم مكان انعقاد الدورة
    public function getVenueIdNameAttribute()
    {
        if ($this->venue) {
            return $this->venue->name ?? $this->venue->name ?? 'غير محدد';
        }
        return 'غير محدد';
    }
}