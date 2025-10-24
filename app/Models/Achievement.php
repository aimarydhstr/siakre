<?php
// app/Models/Achievement.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id', 'team', 'type_achievement', 'field', 'level', 'competition', 'rank', 'organizer', 'month', 'year'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_achievements')
            ->withPivot('certificate');
    }

    public function documentations()
    {
        return $this->hasMany(AchievementDocumentation::class);
    }
}
