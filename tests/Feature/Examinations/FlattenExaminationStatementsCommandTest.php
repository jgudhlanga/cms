<?php

use App\Support\Examinations\ExaminationDumpColumns;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

it('flattens a HEXCO statement xlsx into an exam-dump csv', function (): void {
    $inputRelative = 'examinations/test-statement.xlsx';
    $inputPath = storage_path('app/'.$inputRelative);
    @mkdir(dirname($inputPath), 0775, true);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Sheet1');

    $rows = [
        ['', '', '', 'HIGHER EDUCATION EXAMINATIONS COUNCIL'],
        ['', '', '', 'CANDIDATE NUMBER', '', ':', '9999001A00001'],
        ['', '', '', 'COMMENT', '', ':', 'AWARD'],
        ['', '', '', 'SURNAME', '', ':', 'Doe'],
        ['', '', '', 'FIRST NAMES', '', ':', 'Jane'],
        ['', '', '', 'COURSE TITLE', '', ':', 'Test Course'],
        ['', '', '', 'PAPER No.', 'APPROVED SUBJECT / MODULE TITLES', '', '', 'GRADE', 'DATE'],
        ['', '', '', '100/S01', 'Sample Subject', '', '', 'C', '06/2026'],
    ];

    foreach ($rows as $r => $cols) {
        foreach ($cols as $c => $value) {
            $sheet->setCellValue([$c + 1, $r + 1], $value);
        }
    }

    (new Xlsx($spreadsheet))->save($inputPath);
    $spreadsheet->disconnectWorksheets();

    $outputPath = storage_path('app/examinations/flattened/test-statement-exam-dump.csv');

    $exit = Artisan::call('examinations:flatten-statements', [
        'input' => $inputPath,
        '--output' => $outputPath,
    ]);

    expect($exit)->toBe(0)
        ->and(is_file($outputPath))->toBeTrue();

    $csv = array_map('str_getcsv', file($outputPath));

    expect($csv[0])->toBe(ExaminationDumpColumns::requiredHeaders())
        ->and($csv[1][0])->toBe('Test Course')
        ->and($csv[1][2])->toBe('9999001A00001')
        ->and($csv[1][5])->toBe('100/S01')
        ->and($csv[1][8])->toBe('2026-06-01');

    @unlink($inputPath);
    @unlink($outputPath);
});
