<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'templates';

    protected $fillable = [
        'title',
        'text',
        'type',
        'company_id',
        'status',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function messageType()
    {
        return $this->belongsTo(MessageType::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
