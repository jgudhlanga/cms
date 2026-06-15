<?php

declare(strict_types=1);

namespace App\Support\Exports;

use App\Models\Shared\Address;
use App\Models\Students\Student;
use App\Models\Users\User;
use Illuminate\Support\Carbon;

class StudentExportRowMapper
{
    public function resolveFirstNames(?string $firstName, ?string $middleName): ?string
    {
        $parts = array_filter([$firstName, $middleName]);

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    public function resolveNationalId(?Student $student): ?string
    {
        if ($student === null) {
            return null;
        }

        if (! $student->isZimbabwean()) {
            return $student->passport_number ?: $student->id_number;
        }

        return $student->id_number;
    }

    public function resolveMainAddress(?Student $student): ?Address
    {
        if ($student === null) {
            return null;
        }

        return $student->addresses->firstWhere('address_is_main', true)
            ?? $student->addresses->first();
    }

    public function resolveInternationalFlag(?Student $student): string
    {
        return $student !== null && ! $student->isZimbabwean() ? 'Yes' : 'No';
    }

    public function resolveDateOfBirth(?Student $student): ?string
    {
        if ($student?->date_of_birth === null) {
            return null;
        }

        return Carbon::parse($student->date_of_birth)->format('d/m/Y');
    }

    public function resolveEmail(?User $user, ?Student $student): ?string
    {
        if ($user?->email !== null && $user->email !== '') {
            return $user->email;
        }

        return $student?->contacts->first()?->email_address;
    }

    public function resolvePhone(?Student $student): ?string
    {
        return $student?->contacts->first()?->phone_number;
    }
}
