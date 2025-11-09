<?php
// app/Models/Lecturer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'department_id','nik','nidn','birth_place','birth_date','address',
        'position','marital_status','expertise_field_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function expertiseField()
    {
        return $this->belongsTo(ExpertiseField::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'lecturer_articles');
    }
    
    public function hkis()
    {
        return $this->belongsToMany(Hki::class, 'hki_lecturers', 'lecturer_id', 'hki_id')
                    ->withTimestamps();
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_lecturers', 'lecturer_id', 'book_id')
                    ->withTimestamps();
    }
}
