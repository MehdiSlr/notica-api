<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table ='message_types';

    protected $fillable = [
        'title',
        'description',
        'variables',
        'is_active'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];

    public function templates()
    {
        return $this->hasMany(Template::class, 'type');
    }
}
