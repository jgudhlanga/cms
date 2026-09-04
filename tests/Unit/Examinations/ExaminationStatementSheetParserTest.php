<?php

use App\Support\Examinations\ExaminationDumpColumns;
use App\Support\Examinations\ExaminationStatementSheetParser;

function hexcoStatementSheet1Fixture(): array
{
    return [
        ['', 19480390575588.0, '', 'MINISTRY OF HIGHER AND TERTIARY EDUCATION, SCIENCE AND TECHNOLOGY DEVELOPMENT', '', '', '', '', ''],
        ['', '', '', "HIGHER EDUCATION EXAMINATIONS COUNCIL\n(HEXCO)\n\nINDIVIDUAL STATEMENT OF RESULTS", '', '', '', '', ''],
        ['', '', '1112001I00144', '', '', '', '', '', ''],
        ['', '', '', 'CANDIDATE NUMBER', '', ':', '1112001I00144', '', ''],
        ['', '', '', 'COMMENT', '', ':', 'AWARD', '', ''],
        ['', '', '', 'SURNAME', '', ':', 'Masinire', '', ''],
        ['', '', '', 'FIRST NAMES', '', ':', 'Innocent', '', ''],
        ['', '', '', 'INSTITUTION NAME', '', ':', 'Harare Polytechnic', '', ''],
        ['', '', '', 'COURSE LEVEL', '', ':', 'N.C.', '', ''],
        ['', '', '', 'COURSE TITLE', '', ':', 'Electrical Power Engineering', '', ''],
        ['', '', '', 'PAPER No.', 'APPROVED SUBJECT / MODULE TITLES', '', '', 'GRADE', 'DATE'],
        ['', '', '', '310/S07', 'Introduction To Computers', '', '', 'E', '11/2012'],
        ['', '', '', '321/S09', 'On The Job Training', '', '', 'C', '06/2026'],
        ['', '', '', '402/S01', 'Entrepreneurship Skills Development', '', '', 'C', '11/2012'],
    ];
}

it('parses a HEXCO statement sheet into exam-dump rows', function (): void {
    $rows = app(ExaminationStatementSheetParser::class)->parse(hexcoStatementSheet1Fixture());

    expect($rows)->toHaveCount(3)
        ->and($rows[0][ExaminationDumpColumns::CANDIDATE_NUMBER])->toBe('1112001I00144')
        ->and($rows[0][ExaminationDumpColumns::SURNAME])->toBe('Masinire')
        ->and($rows[0][ExaminationDumpColumns::FIRST_NAMES])->toBe('Innocent')
        ->and($rows[0][ExaminationDumpColumns::DISCIPLINE])->toBe('Electrical Power Engineering')
        ->and($rows[0][ExaminationDumpColumns::SUBJECT_CODE])->toBe('310/S07')
        ->and($rows[0][ExaminationDumpColumns::SUBJECT])->toBe('Introduction To Computers')
        ->and($rows[0][ExaminationDumpColumns::GRADE])->toBe('E')
        ->and($rows[0][ExaminationDumpColumns::SESSION])->toBe('2012-11-01')
        ->and($rows[0][ExaminationDumpColumns::COURSE_COMMENT])->toBe('AWARD')
        ->and($rows[1][ExaminationDumpColumns::SESSION])->toBe('2026-06-01');
});

it('carries the statement course level onto every dump row', function (): void {
    $rows = app(ExaminationStatementSheetParser::class)->parse(hexcoStatementSheet1Fixture());

    expect($rows)->each->toHaveKey(ExaminationDumpColumns::COURSE_LEVEL)
        ->and(collect($rows)->pluck(ExaminationDumpColumns::COURSE_LEVEL)->unique()->all())->toBe(['N.C.']);
});

it('filters statement subject rows by sitting', function (): void {
    $rows = app(ExaminationStatementSheetParser::class)->parse(
        hexcoStatementSheet1Fixture(),
        onlySession: '06/2026',
    );

    expect($rows)->toHaveCount(1)
        ->and($rows[0][ExaminationDumpColumns::SUBJECT_CODE])->toBe('321/S09')
        ->and($rows[0][ExaminationDumpColumns::SESSION])->toBe('2026-06-01');
});

it('converts mm/yyyy sitting labels to iso dates', function (): void {
    $parser = app(ExaminationStatementSheetParser::class);

    expect($parser->sessionToIsoDate('06/2026'))->toBe('2026-06-01')
        ->and($parser->sessionToIsoDate('11/2012'))->toBe('2012-11-01')
        ->and($parser->sessionToIsoDate('bogus'))->toBeNull();
});
