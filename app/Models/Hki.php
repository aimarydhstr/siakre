<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hki extends Model
{
    protected $fillable = ['name','number','holder', 'date','file','department_id'];

    // Dosen yang terlibat sebagai inventor
    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'hki_lecturers', 'hki_id', 'lecturer_id')
                    ->withTimestamps();
    }

    // Mahasiswa yang terlibat sebagai inventor
    public function students()
    {
        return $this->belongsToMany(Student::class, 'hki_students', 'hki_id', 'student_id')
                    ->withTimestamps();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
