<?php
// app/Models/LecturerArticle.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LecturerArticle extends Model
{
    use HasFactory;

    protected $fillable = ['lecturer_id', 'article_id'];

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
