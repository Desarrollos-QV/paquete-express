<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geozones extends Model
{

    protected $table = "zones";

    protected $fillable = [
        'name',
        'zone_name',
        'coverage',
        'coords',
        'cp',
        'price',
        'status'
    ];

}