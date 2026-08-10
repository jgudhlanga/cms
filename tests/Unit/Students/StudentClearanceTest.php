<?php

declare(strict_types=1);

use App\Enums\Students\StudentClearanceSection;
use App\Models\Students\StudentClearance;

test('isAccountsCleared reflects accounts section only', function () {
    $clearance = new StudentClearance([
        'accounts_cleared' => true,
        'library_cleared' => false,
        'security_cleared' => false,
        'hostel_cleared' => false,
        'department_cleared' => false,
    ]);

    expect($clearance->isAccountsCleared())->toBeTrue()
        ->and($clearance->isFullyCleared())->toBeFalse();
});

test('student clearance is fully cleared only when all sections are cleared', function () {
    $clearance = new StudentClearance([
        'accounts_cleared' => true,
        'library_cleared' => true,
        'security_cleared' => true,
        'hostel_cleared' => true,
        'department_cleared' => false,
    ]);

    expect($clearance->isFullyCleared())->toBeFalse()
        ->and($clearance->pendingSections())->toBe([StudentClearanceSection::Department->value]);

    $clearance->department_cleared = true;

    expect($clearance->isFullyCleared())->toBeTrue()
        ->and($clearance->pendingSections())->toBe([]);
});

test('clearance section permissions follow student-clearance prefix', function () {
    expect(StudentClearanceSection::Accounts->permission())->toBe('student-clearance:accounts')
        ->and(StudentClearanceSection::Library->permission())->toBe('student-clearance:library')
        ->and(StudentClearanceSection::Security->permission())->toBe('student-clearance:security')
        ->and(StudentClearanceSection::Hostel->permission())->toBe('student-clearance:hostel')
        ->and(StudentClearanceSection::Department->permission())->toBe('student-clearance:department');
});
