<?php

declare(strict_types=1);

namespace App\Enums\Students;

enum ApplicationTrackEnum: string
{
    case Regular = 'regular';
    case Continuous = 'continuous';
    case Apprentice = 'apprentice';

    public function label(): string
    {
        return match ($this) {
            self::Regular => __('trans.application_track_regular'),
            self::Continuous => __('trans.application_track_continuous'),
            self::Apprentice => __('trans.application_track_apprentice'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Regular => __('trans.application_track_regular_description'),
            self::Continuous => __('trans.application_track_continuous_description'),
            self::Apprentice => __('trans.application_track_apprentice_description'),
        };
    }

    public function skipsApplicationFee(): bool
    {
        return $this === self::Apprentice;
    }

    public function usesContinuousIntake(): bool
    {
        return $this === self::Continuous;
    }
}
