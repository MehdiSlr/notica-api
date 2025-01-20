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

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'established_date'
    ];

    protected $casts = [
        'settings' => 'array',
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'from');
    }

    public function templates()
    {
        return $this->hasMany(Template::class, 'company_id');
    }

    public function plans()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class, 'company_id');
    }
}
