<?php
// app/Models/Department.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'faculty_id'];

    public function departmentHeads()
    {
        return $this->hasMany(DepartmentHead::class);
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}
