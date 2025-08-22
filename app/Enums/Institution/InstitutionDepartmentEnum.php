<?php

namespace App\Enums\Institution;

enum InstitutionDepartmentEnum: string
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

    public function departmentCode(): ?string
    {
        return match ($this) {
            self::ADMINISTRATION,
            self::CLINIC, self::DEAN_OF_STUDENTS,
            self::EXECUTIVE, self::FINANCE,
            self::HUMAN_RESOURCES, self::IT_UNIT,
            self::PROCUREMENT_MANAGEMENT_UNIT => null,
            self::APPLIED_ARTS => 'AA01',
            self::AUTOMOTIVE_ENGINEERING => 'AE02',
            self::BUSINESS_AND_MANAGEMENT_STUDIES => 'COM03',
            self::CIVIL_ENGINEERING => 'CIV04',
            self::CONSTRUCTION_ENGINEERING => 'CE05',
            self::ELECTRICAL_ENGINEERING => 'EE06',
            self::INFORMATION_COMMUNICATION_TECHNOLOGY => 'ICT07',
            self::LIBRARY_AND_INFORMATION_SCIENCES => 'LIS08',
            self::MASS_COMMUNICATION => 'JMS12',
            self::MECHANICAL_AND_PRODUCTION_ENGINEERING => 'ME09',
            self::OFFICE_MANAGEMENT => 'OM14',
            self::PRINTING_AND_GRAPHIC_ARTS => 'PGA10',
            self::SCIENCE_TECHNOLOGY => 'SCI13',
            self::TOURISM_AND_HOSPITALITY => 'HT11',
            self::CHEMICAL_TECHNOLOGY => "BTC13",
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

