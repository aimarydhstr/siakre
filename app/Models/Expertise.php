<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expertise extends Model
{
    protected $fillable = ['name'];

    public function fields() {
        return $this->hasMany(ExpertiseField::class);
    }
}

?>