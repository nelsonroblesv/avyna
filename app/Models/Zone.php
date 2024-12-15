<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $fillable = ['name', 'color', 'state_id'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
    // Relación con ZoneLocation
    public function zoneLocations()
    {
        return $this->hasMany(ZoneLocation::class);
    }
}
