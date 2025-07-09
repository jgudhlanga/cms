<?php

namespace App\Enums\Institution;

enum DepartmentEnum: string
{
    case APPLIED_ARTS = "Applied Arts";
    case AUTOMOTIVE_ENGINEERING = "Automotive Engineering";
    case BUSINESS_AND_MANAGEMENT_STUDIES = "Business And Management Studies";
    case CIVIL_ENGINEERING = "Civil Engineering";
    case CONSTRUCTION_ENGINEERING = "Construction Engineering";
    case ELECTRICAL_ENGINEERING = "Electrical Engineering";
    case EXECUTIVE = "Executive";
    case INFORMATION_COMMUNICATION_TECHNOLOGY = "Information Communication Technology";
    case LIBRARY_AND_INFORMATION_SCIENCES = "Library And Information Sciences";
    case MASS_COMMUNICATION = "Mass Communication";
    case MECHANICAL_AND_PRODUCTION_ENGINEERING = "Mechanical And Production Engineering";
    case OFFICE_MANAGEMENT = "Office Management";
    case PRINTING_AND_GRAPHIC_ARTS = "Printing And Graphics Arts";
    case SCIENCE_TECHNOLOGY = "Science Technology";
    case TOURISM_AND_HOSPITALITY = "Tourism And Hospitality";

    public function label(): string
    {
        return match ($this) {
            self::APPLIED_ARTS => "Applied Arts",
            self::AUTOMOTIVE_ENGINEERING => "Automotive Engineering",
            self::BUSINESS_AND_MANAGEMENT_STUDIES => "Business And Management Studies",
            self::CIVIL_ENGINEERING => "Civil Engineering",
            self::CONSTRUCTION_ENGINEERING => "Construction Engineering",
            self::ELECTRICAL_ENGINEERING => "Electrical Engineering",
            self::EXECUTIVE => "Executive",
            self::INFORMATION_COMMUNICATION_TECHNOLOGY => "Information Communication Technology",
            self::LIBRARY_AND_INFORMATION_SCIENCES => "Library And Information Sciences",
            self::MASS_COMMUNICATION => "Mass Communication",
            self::MECHANICAL_AND_PRODUCTION_ENGINEERING => "Mechanical And Production Engineering",
            self::OFFICE_MANAGEMENT => "Office Management",
            self::PRINTING_AND_GRAPHIC_ARTS => "Printing And Graphics Arts",
            self::SCIENCE_TECHNOLOGY => "Science Technology",
            self::TOURISM_AND_HOSPITALITY => "Tourism And Hospitality",
        };
    }

    public function position(): int
    {
        return match ($this) {
            self::APPLIED_ARTS => 1,
            self::AUTOMOTIVE_ENGINEERING => 2,
            self::BUSINESS_AND_MANAGEMENT_STUDIES => 3,
            self::CIVIL_ENGINEERING => 4,
            self::CONSTRUCTION_ENGINEERING => 5,
            self::ELECTRICAL_ENGINEERING => 6,
            self::EXECUTIVE => 7,
            self::INFORMATION_COMMUNICATION_TECHNOLOGY => 8,
            self::LIBRARY_AND_INFORMATION_SCIENCES => 9,
            self::MASS_COMMUNICATION => 10,
            self::MECHANICAL_AND_PRODUCTION_ENGINEERING => 11,
            self::OFFICE_MANAGEMENT => 12,
            self::PRINTING_AND_GRAPHIC_ARTS => 13,
            self::SCIENCE_TECHNOLOGY => 14,
            self::TOURISM_AND_HOSPITALITY => 15,
        };
    }

    public static function all(): array
    {
        return collect(self::cases())
            ->sortBy(fn($case) => $case->position())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}

