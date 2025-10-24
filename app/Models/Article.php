<?php
// app/Models/Article.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id', 'title', 'type_journal', 'url', 'publisher', 'date', 'category', 'volume', 'number', 'file'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_articles');
    }

    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'lecturer_articles');
    }
}
