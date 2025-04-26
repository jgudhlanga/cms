<?php

namespace App\Enums;

enum DepartmentEnum: string
{
    case APPLIED_ARTS = "Applied Arts";
    case AUTOMOTIVE_ENGINEERING = "Automotive Engineering";
    case BUSINESS_AND_MANAGEMENT_STUDIES = "Business & Management Studies";
    case CIVIL_ENGINEERING = "Civil Engineering";
    case CONSTRUCTION_ENGINEERING = "Construction Engineering";
    case ELECTRICAL_ENGINEERING = "Electrical Engineering";
    case ICT = 'ICT';
    case LIBRARY_AND_INFORMATION_SCIENCES = "Library & Info Sciences";
    case MASS_COMMUNICATION = "Mass Communication";
    case MECHANICAL_AND_PRODUCTION_ENGINEERING = "Mechanical & Production Engineering";
    case OFFICE_MANAGEMENT = "Office Management";
    case PRINTING_AND_GRAPHIC_ARTS = "Printing And Graphics Arts";
    case SCIENCE_TECHNOLOGY = "Science Technology";
    case TOURISM_AND_HOSPITALITY = "Tourism And Hospitality";


    public function label(): string
    {
        return match ($this) {
            self::APPLIED_ARTS => "Applied Arts",
            self::AUTOMOTIVE_ENGINEERING => "Automotive Engineering",
            self::BUSINESS_AND_MANAGEMENT_STUDIES => "Business & Management Studies",
            self::CIVIL_ENGINEERING => "Civil Engineering",
            self::CONSTRUCTION_ENGINEERING => "Construction Engineering",
            self::ELECTRICAL_ENGINEERING => "Electrical Engineering",
            self::ICT => "ICT",
            self::LIBRARY_AND_INFORMATION_SCIENCES => "Library & Information Sciences",
            self::MASS_COMMUNICATION => "Mass Communication",
            self::MECHANICAL_AND_PRODUCTION_ENGINEERING => "Mechanical & Production Engineering",
            self::OFFICE_MANAGEMENT => "Office Management",
            self::PRINTING_AND_GRAPHIC_ARTS => "Print and Graphics Arts",
            self::SCIENCE_TECHNOLOGY => "Science Technology",
            self::TOURISM_AND_HOSPITALITY => "Tourism And Hospitality",
        };
    }

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
