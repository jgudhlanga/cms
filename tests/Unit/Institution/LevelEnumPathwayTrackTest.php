<?php

declare(strict_types=1);

use App\Enums\Institution\LevelEnum;

it('keeps HEXCO levels on one track and SDP on its own', function (LevelEnum $level, string $track): void {
    expect($level->pathwayTrack())->toBe($track);
})->with([
    [LevelEnum::NC, 'hexco'],
    [LevelEnum::ND, 'hexco'],
    [LevelEnum::HND, 'hexco'],
    [LevelEnum::BTECH, 'hexco'],
    [LevelEnum::SDP, 'sdp'],
    [LevelEnum::ABMA_LEVEL_3, 'abma'],
    [LevelEnum::ABMA_LEVEL_4, 'abma'],
    [LevelEnum::ABMA_LEVEL_5, 'abma'],
    [LevelEnum::ABMA_LEVEL_6, 'abma'],
]);
