<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ia extends Model
{
    protected $fillable = [
        'cooperation_id','mou_name','ia_name','file','proof',
    ];

    public function cooperation()
    {
        return $this->belongsTo(Cooperation::class, 'cooperation_id');
    }
}
