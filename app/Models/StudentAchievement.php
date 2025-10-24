<?php
// app/Models/StudentAchievement.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'achievement_id', 'certificate'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function achievement()
    {
        return $this->belongsTo(Achievement::class);
    }
}
