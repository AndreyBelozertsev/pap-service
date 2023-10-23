<?php

namespace Services\AmoCRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmoCrmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'expires_in',
        'access_token',
        'refresh_token',
        'base_domain',
        'source',
    ];

    public function integration()
    {
        return $this->belongsTo(AmoCrmIntegration::class);
    }
}
