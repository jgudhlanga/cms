<?php

namespace App\Support\HMS;

final class HostelApplicationPaymentVerification
{
    public const KEY_ADDRESS_OUTSIDE_CITY = 'address_outside_city_campus_confirmed';

    public const KEY_FULL_TIME_STUDENT = 'full_time_student_confirmed';

    public const KEY_TUITION_FEES_PAID = 'tuition_fees_paid_confirmed';

    public const KEY_ACCOMMODATION_FEES_PAID = 'accommodation_fees_paid_confirmed';

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            self::KEY_ADDRESS_OUTSIDE_CITY => false,
            self::KEY_FULL_TIME_STUDENT => false,
            self::KEY_TUITION_FEES_PAID => false,
            self::KEY_ACCOMMODATION_FEES_PAID => false,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function normalize(?array $stored): array
    {
        return array_merge(self::defaults(), $stored ?? []);
    }

    /**
     * @return array<string, bool>
     */
    public static function fromApi(?array $input): array
    {
        if ($input === null) {
            return self::defaults();
        }

        $map = [
            'addressOutsideCityCampusConfirmed' => self::KEY_ADDRESS_OUTSIDE_CITY,
            'fullTimeStudentConfirmed' => self::KEY_FULL_TIME_STUDENT,
            'tuitionFeesPaidConfirmed' => self::KEY_TUITION_FEES_PAID,
            'accommodationFeesPaidConfirmed' => self::KEY_ACCOMMODATION_FEES_PAID,
        ];

        $merged = self::defaults();

        foreach ($map as $camel => $snake) {
            if (array_key_exists($camel, $input)) {
                $merged[$snake] = (bool) $input[$camel];
            } elseif (array_key_exists($snake, $input)) {
                $merged[$snake] = (bool) $input[$snake];
            }
        }

        return $merged;
    }

    /**
     * @return array<string, bool>
     */
    public static function toApi(?array $stored): array
    {
        $normalized = self::normalize($stored);

        return [
            'addressOutsideCityCampusConfirmed' => $normalized[self::KEY_ADDRESS_OUTSIDE_CITY],
            'fullTimeStudentConfirmed' => $normalized[self::KEY_FULL_TIME_STUDENT],
            'tuitionFeesPaidConfirmed' => $normalized[self::KEY_TUITION_FEES_PAID],
            'accommodationFeesPaidConfirmed' => $normalized[self::KEY_ACCOMMODATION_FEES_PAID],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function merge(?array $stored, ?array $input): array
    {
        return self::fromApi(array_merge(self::toApi($stored), $input ?? []));
    }

    /**
     * @param  array<string, mixed>|null  $input
     */
    public static function isCompleteFromApi(?array $input): bool
    {
        if ($input === null) {
            return false;
        }

        $requiredKeys = [
            'addressOutsideCityCampusConfirmed',
            'fullTimeStudentConfirmed',
            'tuitionFeesPaidConfirmed',
            'accommodationFeesPaidConfirmed',
        ];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $input)) {
                return false;
            }
        }

        return true;
    }
}
