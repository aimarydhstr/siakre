<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['isbn','title','file', 'publisher','publish_year','publish_month','city','department_id'];

    protected $casts = [
        'publish_year' => 'integer',
    ];

    // Penulis dari dosen
    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'book_lecturers', 'book_id', 'lecturer_id')
                    ->withTimestamps();
    }

    // Penulis dari mahasiswa
    public function students()
    {
        return $this->belongsToMany(Student::class, 'book_students', 'book_id', 'student_id')
                    ->withTimestamps();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
