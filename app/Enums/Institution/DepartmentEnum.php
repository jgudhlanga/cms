<?php

namespace App\Enums\Institution;

enum DepartmentEnum: string
{
    case ADMINISTRATION = "Administration";
    case APPLIED_ARTS = "Applied Arts";
    case AUTOMOTIVE_ENGINEERING = "Automotive Engineering";
    case BUSINESS_AND_MANAGEMENT_STUDIES = "Business And Management Studies";
    case CHEMICAL_TECHNOLOGY = "Chemical Technology";
    case CIVIL_ENGINEERING = "Civil Engineering";
    case CLINIC = "Clinic";
    case CONSTRUCTION_ENGINEERING = "Construction Engineering";
    case DEAN_OF_STUDENTS = "Dean Of Students";
    case ELECTRICAL_ENGINEERING = "Electrical Engineering";
    case EXECUTIVE = "Executive";
    case FINANCE = "Finance";
    case HUMAN_RESOURCES = "Human Resources";
    case INFORMATION_COMMUNICATION_TECHNOLOGY = "Information Communication Technology";
    case IT_UNIT = "IT Unit";
    case LIBRARY_AND_INFORMATION_SCIENCES = "Library And Information Sciences";
    case MASS_COMMUNICATION = "Mass Communication";
    case MECHANICAL_AND_PRODUCTION_ENGINEERING = "Mechanical And Production Engineering";
    case OFFICE_MANAGEMENT = "Office Management";
    case PRINTING_AND_GRAPHIC_ARTS = "Printing And Graphics Arts";
    case PROCUREMENT_MANAGEMENT_UNIT = "Procurement Management Unit";
    case SCIENCE_TECHNOLOGY = "Science Technology";
    case TOURISM_AND_HOSPITALITY = "Tourism And Hospitality";

    public function label(): string
    {
        return $this->value;
    }

    public function isAcademic(): bool
    {
        return match ($this) {
            self::ADMINISTRATION,
            self::CLINIC, self::DEAN_OF_STUDENTS,
            self::EXECUTIVE, self::FINANCE,
            self::HUMAN_RESOURCES, self::IT_UNIT,
            self::PROCUREMENT_MANAGEMENT_UNIT => false,
            self::APPLIED_ARTS,
            self::AUTOMOTIVE_ENGINEERING,
            self::BUSINESS_AND_MANAGEMENT_STUDIES,
            self::CHEMICAL_TECHNOLOGY,
            self::CIVIL_ENGINEERING,
            self::CONSTRUCTION_ENGINEERING,
            self::ELECTRICAL_ENGINEERING,
            self::INFORMATION_COMMUNICATION_TECHNOLOGY,
            self::LIBRARY_AND_INFORMATION_SCIENCES,
            self::MASS_COMMUNICATION,
            self::MECHANICAL_AND_PRODUCTION_ENGINEERING,
            self::OFFICE_MANAGEMENT,
            self::PRINTING_AND_GRAPHIC_ARTS,
            self::SCIENCE_TECHNOLOGY,
            self::TOURISM_AND_HOSPITALITY => true,
        };
    }

    public function position(): int
    {
        return match ($this) {
            self::ADMINISTRATION => 1,
            self::APPLIED_ARTS => 2,
            self::AUTOMOTIVE_ENGINEERING => 3,
            self::BUSINESS_AND_MANAGEMENT_STUDIES => 4,
            self::CHEMICAL_TECHNOLOGY => 5,
            self::CIVIL_ENGINEERING => 6,
            self::CLINIC => 7,
            self::CONSTRUCTION_ENGINEERING => 8,
            self::DEAN_OF_STUDENTS => 9,
            self::ELECTRICAL_ENGINEERING => 10,
            self::EXECUTIVE => 11,
            self::FINANCE => 12,
            self::HUMAN_RESOURCES => 13,
            self::INFORMATION_COMMUNICATION_TECHNOLOGY => 14,
            self::IT_UNIT => 15,
            self::LIBRARY_AND_INFORMATION_SCIENCES => 16,
            self::MASS_COMMUNICATION => 17,
            self::MECHANICAL_AND_PRODUCTION_ENGINEERING => 18,
            self::OFFICE_MANAGEMENT => 19,
            self::PRINTING_AND_GRAPHIC_ARTS => 20,
            self::PROCUREMENT_MANAGEMENT_UNIT => 21,
            self::SCIENCE_TECHNOLOGY => 22,
            self::TOURISM_AND_HOSPITALITY => 23,
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

