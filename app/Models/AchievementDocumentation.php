<?php
// app/Models/AchievementDocumentation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchievementDocumentation extends Model
{
    use HasFactory;

    protected $fillable = ['image', 'achievement_id'];

    public function achievement()
    {
        return $this->belongsTo(Achievement::class);
    }
}
