<?php

use App\Services\AcademicCalendars\AcademicCalendarClassNameFormatter;

beforeEach(function () {
    $this->formatter = new AcademicCalendarClassNameFormatter;
});

test('formats class name as level-mode-number without config id', function () {
    expect($this->formatter->format('NC', 'FULL TIME', 1))->toBe('NC-FULL-TIME-1')
        ->and($this->formatter->format('Level 1', 'Full Time', 2))->toBe('LEVEL-1-FULL-TIME-2');
});

test('extracts class number from canonical names', function () {
    expect($this->formatter->extractClassNumber('NC-FULL-TIME-1', 'NC', 'FULL TIME'))->toBe(1)
        ->and($this->formatter->extractClassNumber('LEVEL-1-FULL-TIME-2', 'Level 1', 'Full Time'))->toBe(2);
});

test('extracts class number from legacy names with config suffix', function () {
    expect($this->formatter->extractClassNumber('NC - FULL TIME - 1 - 8', 'NC', 'FULL TIME'))->toBe(1)
        ->and($this->formatter->extractClassNumber('Level 1 - Full Time - 2 - 15', 'Level 1', 'Full Time'))->toBe(2);
});

test('extracts class number from legacy names without config suffix', function () {
    expect($this->formatter->extractClassNumber('Level 1 - Full Time - 3', 'Level 1', 'Full Time'))->toBe(3);
});

test('returns highest class number across mixed legacy and canonical names', function () {
    $highest = $this->formatter->extractHighestClassNumber([
        'NC - FULL TIME - 1 - 8',
        'NC-FULL-TIME-2',
        'NC - FULL TIME - 3',
    ], 'NC', 'FULL TIME');

    expect($highest)->toBe(3);
});

test('returns null for manually renamed classes', function () {
    expect($this->formatter->extractClassNumber('Renamed class', 'NC', 'FULL TIME'))->toBeNull();
});
