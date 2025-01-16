<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    protected $table = 'codes';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
