<?php

use App\Rules\ZimbabweanIdNumber;
use Tests\TestCase;

uses(TestCase::class);
use Illuminate\Support\Facades\Validator;

test('zimbabwean id number rule accepts valid hyphenated format', function () {
    $validator = Validator::make(
        ['id_number' => '63-1234567N63'],
        ['id_number' => [new ZimbabweanIdNumber]],
    );

    expect($validator->passes())->toBeTrue();
});

test('zimbabwean id number rule rejects invalid format', function () {
    $validator = Validator::make(
        ['id_number' => 'invalid-id'],
        ['id_number' => [new ZimbabweanIdNumber]],
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('id_number'))->toBe(__('trans.enrollment_invalid_national_id'));
});
