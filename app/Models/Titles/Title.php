<?php

namespace App\Models\Titles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Title extends Model
{
    /** @use HasFactory<\Database\Factories\Titles\TitleFactory> */
    use HasFactory;
    protected $fillable=["name"];
}
