<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'messages';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $hidden = [
        'from',
        'to',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'platform' => 'array',
    ];

    public function companies()
    {
        return $this->belongsTo(Company::class, 'from');
    }

    public function templates()
    {
        return $this->belongsTo(Template::class);
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'to');
    }
}
