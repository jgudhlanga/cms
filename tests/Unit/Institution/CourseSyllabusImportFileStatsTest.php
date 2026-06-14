<?php

use App\Services\Institution\CourseSyllabusImportFileStats;

it('computes file stats for rows with reused module codes across courses', function () {
    $sharedModuleCode = '402/25/M01';

    $parsedRows = [
        [
            'number' => 1,
            'data' => [
                'COURSE_CODE' => '307/25/CO/M0',
                'MODULE_CODE' => $sharedModuleCode,
                'MODULE_TITLE' => 'Entrepreneurial Skills Development',
            ],
        ],
        [
            'number' => 2,
            'data' => [
                'COURSE_CODE' => '372/25/CO/0',
                'MODULE_CODE' => $sharedModuleCode,
                'MODULE_TITLE' => 'Entrepreneurial Skills Development',
            ],
        ],
        [
            'number' => 3,
            'data' => [
                'COURSE_CODE' => '333/25/CO/M0',
                'MODULE_CODE' => $sharedModuleCode,
                'MODULE_TITLE' => 'Entrepreneurial Skills Development',
            ],
        ],
    ];

    $stats = CourseSyllabusImportFileStats::fromParsedRows($parsedRows);

    expect($stats)->toBe([
        'totalRows' => 3,
        'uniqueCourseCodes' => 3,
        'uniqueModuleCodes' => 1,
        'uniqueModuleRecords' => 3,
        'duplicateModuleCodeGroups' => 1,
        'extraRowsFromDuplicateModuleCodes' => 2,
        'moduleRows' => 3,
        'moduleSkipRows' => 0,
    ]);
});

it('counts module skip rows without module code or title', function () {
    $stats = CourseSyllabusImportFileStats::fromParsedRows([
        [
            'number' => 1,
            'data' => [
                'COURSE_CODE' => 'CT/26/101',
                'MODULE_CODE' => '',
                'MODULE_TITLE' => '',
            ],
        ],
        [
            'number' => 2,
            'data' => [
                'COURSE_CODE' => 'CT/26/101',
                'MODULE_CODE' => 'MOD-1',
                'MODULE_TITLE' => 'Intro',
            ],
        ],
    ]);

    expect($stats['moduleSkipRows'])->toBe(1)
        ->and($stats['moduleRows'])->toBe(1)
        ->and($stats['uniqueModuleRecords'])->toBe(1);
});
