<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cooperation extends Model
{
    protected $fillable = [
        'letter_number','letter_date','partner','type_coop','level','file','user_id',
    ];

    public function pic()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ias()
    {
        return $this->hasMany(Ia::class, 'cooperation_id');
    }
}
