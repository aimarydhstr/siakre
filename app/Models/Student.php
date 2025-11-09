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

    public function hkis()
    {
        return $this->belongsToMany(Hki::class, 'hki_students', 'student_id', 'hki_id')
                    ->withTimestamps();
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_students', 'student_id', 'book_id')
                    ->withTimestamps();
    }
}
