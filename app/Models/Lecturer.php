<?php
// app/Models/Lecturer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','department_id','nik','nidn','birth_place','birth_date','address',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'lecturer_articles');
    }
}
