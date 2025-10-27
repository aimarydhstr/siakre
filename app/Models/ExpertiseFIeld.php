<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertiseField extends Model
{
    protected $fillable = ['expertise_id','name'];

    public function expertise() {
        return $this->belongsTo(Expertise::class);
    }

    public function lecturers() {
        return $this->hasMany(Lecturer::class, 'expertise_field_id');
    }
}


?>