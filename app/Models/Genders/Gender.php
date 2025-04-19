<?php

namespace App\Models\Genders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    /** @use HasFactory<\Database\Factories\Genders\GenderFactory> */
    use HasFactory;
    protected $fillable=["name"];
}
