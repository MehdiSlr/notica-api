<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'api_keys';

    protected $fillable = [
        'title',
        'key',
        'company_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [
        'secret',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function companies()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
