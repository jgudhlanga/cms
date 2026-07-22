<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Students\ApplicationTrackEnum;
use Illuminate\Support\Facades\Session;

class ApplicationTrackSession
{
    public const TRACK_KEY = 'application.track';

    public const LEVEL_KEY = 'application.level_id';

    public const INTAKE_KEY = 'application.intake_period_id';

    public function get(): ?ApplicationTrackEnum
    {
        $value = Session::get(self::TRACK_KEY);

        if (! is_string($value)) {
            return null;
        }

        return ApplicationTrackEnum::tryFrom($value);
    }

    public function set(ApplicationTrackEnum $track): void
    {
        Session::put(self::TRACK_KEY, $track->value);
    }

    public function require(): ApplicationTrackEnum
    {
        return $this->get() ?? ApplicationTrackEnum::Regular;
    }

    public function clear(): void
    {
        Session::forget([self::TRACK_KEY, self::LEVEL_KEY, self::INTAKE_KEY]);
    }

    public function setLevel(int $levelId): void
    {
        Session::put(self::LEVEL_KEY, $levelId);
    }

    public function setIntakePeriodId(int $intakePeriodId): void
    {
        Session::put(self::INTAKE_KEY, $intakePeriodId);
    }

    public function intakePeriodId(): ?int
    {
        $id = Session::get(self::INTAKE_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function levelId(): ?int
    {
        $id = Session::get(self::LEVEL_KEY);

        return $id !== null ? (int) $id : null;
    }
}
