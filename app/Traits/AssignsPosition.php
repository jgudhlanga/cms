<?php

namespace App\Traits;

trait AssignsPosition
{
    public static function bootAssignsPosition(): void
    {
        static::creating(function ($model) {
            if (is_null($model->position)) {
                $max = $model->newQuery()->max('position') ?? 0;
                $model->position = $max + 1;
            }
        });
    }
}
