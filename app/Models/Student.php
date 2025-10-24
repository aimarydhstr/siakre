<?php
// app/Models/Student.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['nim', 'name', 'photo', 'department_id'];

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'student_articles');
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'student_achievements')
            ->withPivot('certificate');
    }
}
