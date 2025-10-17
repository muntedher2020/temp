<?php
namespace App\Models\Trainers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Trainers extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "trainers";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // علاقة مع training_institutions
    public function training_institution()
    {
        // محاولة الحصول على النموذج الصحيح
        if (class_exists('App\Models\TrainingInstitutions\TrainingInstitutions')) {
            return $this->belongsTo('App\Models\TrainingInstitutions\TrainingInstitutions', 'institution_id', 'id');
        } elseif (class_exists('App\Models\TrainingInstitution\TrainingInstitution')) {
            return $this->belongsTo('App\Models\TrainingInstitution\TrainingInstitution', 'institution_id', 'id');
        }
        
        return null;
    }

    // Helper method لجلب اسم مؤسسة المدرب
    public function getInstitutionIdNameAttribute()
    {
        if ($this->training_institution) {
            return $this->training_institution->name ?? $this->training_institution->name ?? 'غير محدد';
        }
        return 'غير محدد';
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
}