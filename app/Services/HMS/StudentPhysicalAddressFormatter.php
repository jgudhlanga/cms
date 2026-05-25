<?php

namespace App\Services\HMS;

use App\Models\Students\Student;

class StudentPhysicalAddressFormatter
{
    public static function fromStudent(?Student $student): ?string
    {
        if ($student === null) {
            return null;
        }

        $student->loadMissing('addresses');
        $studentAddress = $student->addresses->first();

        if ($studentAddress === null) {
            return null;
        }

        $address = trim(implode(', ', array_filter([
            $studentAddress->address_1,
            $studentAddress->address_2,
            $studentAddress->address_3,
            $studentAddress->address_4,
            $studentAddress->address_5,
            $studentAddress->address_6,
        ])));

        return $address !== '' ? $address : null;
    }
}
