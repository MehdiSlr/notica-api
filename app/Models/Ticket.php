<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'body',
        'reply_id',
        'file',
        'company_id',
        'user_id',
        'status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public function companies()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
