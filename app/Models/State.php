<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function municipality(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }
    // Relación con Zone: Un estado tiene muchas zonas
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }
}