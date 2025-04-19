<?php

namespace App\Models\Countries;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    /** @use HasFactory<\Database\Factories\Countries\CountryFactory> */
    use HasFactory;
    protected $fillable=["name", "meta"];

}
