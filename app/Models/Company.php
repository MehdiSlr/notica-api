<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'companies';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'settings' => 'array',
        'email_verified_at' => 'datetime',
        'established_date' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
