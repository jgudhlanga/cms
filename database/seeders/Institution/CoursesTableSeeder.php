<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\CourseEnum;
use App\Models\Institution\Course;
use Illuminate\Database\Seeder;

class CoursesTableSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            'Applied arts' => [
                ['name' => CourseEnum::BEAUTY_THERAPY->value],
                ['name' => CourseEnum::COSMETOLOGY->value],
                ['name' => CourseEnum::HAIRDRESSING->value],
                ['name' => CourseEnum::INDUSTRIAL_CLOTHING_DESIGN_AND_CONSTRUCTION->value],
            ],
            'Applied Science Technology' => [
                ['name' => CourseEnum::APPLIED_BIOLOGICAL_TECHNOLOGY->value],
                ['name' => CourseEnum::APPLIED_CHEMICAL_TECHNOLOGY->value],
                ['name' => CourseEnum::CHEMICAL_ENGINEERING->value],
                ['name' => CourseEnum::CHEMICAL_TECHNOLOGY->value],
                ['name' => CourseEnum::FOOD_SCIENCE->value],
                ['name' => CourseEnum::HORTICULTURE->value],
                ['name' => CourseEnum::LABORATORY_TECHNOLOGY->value],
                ['name' => CourseEnum::METALLURGICAL_ASSAYING->value],
                ['name' => CourseEnum::PHARMACEUTICAL_TECHNOLOGY->value],
                ['name' => CourseEnum::POLYMER_TECHNOLOGY->value],
            ],
            'Automotive' => [
                ['name' => CourseEnum::AUTOMOBILE_ELECTRICS_AND_ELECTRONICS->value],
                ['name' => CourseEnum::AUTOMOTIVE_ENGINEERING->value],
                ['name' => CourseEnum::AUTOMOTIVE_PRECISION_MACHINING->value],
                ['name' => CourseEnum::DIESEL_PLANT_FITTING->value],
                ['name' => CourseEnum::MOTOR_CYCLE_MECHANICS->value],
                ['name' => CourseEnum::MOTOR_VEHICLE_BODY_REPAIRS->value],
                ['name' => CourseEnum::MOTOR_VEHICLE_MECHANICS->value],
            ],
            'Commerce' => [
                ['name' => CourseEnum::ACCOUNTANCY->value],
                ['name' => CourseEnum::BANKING_AND_FINANCE->value],
                ['name' => CourseEnum::HEALTH_SERVICES_MANAGEMENT->value],
                ['name' => CourseEnum::HUMAN_RESOURCES_MANAGEMENT->value],
                ['name' => CourseEnum::PENSIONS_AND_INVESTMENTS_MANAGEMENT->value],
                ['name' => CourseEnum::PURCHASING_AND_SUPPLY_MANAGEMENT->value],
                ['name' => CourseEnum::SALES_AND_MARKETING_MANAGEMENT->value],
                ['name' => CourseEnum::TRAINERS_DIPLOMA_IN_EDUCATION->value],
                ['name' => CourseEnum::TRANSPORT_AND_LOGISTICS_MANAGEMENT->value],
            ],
            'Civil Engineering' => [
                ['name' => CourseEnum::ARCHITECTURAL_TECHNOLOGY->value],
                ['name' => CourseEnum::CARTOGRAPHY_AND_GEO_VISUALISATION_TECHNOLOGY->value],
                ['name' => CourseEnum::CIVIL_ENGINEERING->value],
                ['name' => CourseEnum::QUANTITY_SURVEYING->value],
                ['name' => CourseEnum::SURVEYING_AND_GEOMATICS->value],
                ['name' => CourseEnum::URBAN_AND_REGIONAL_PLANNING->value],
                ['name' => CourseEnum::VALUATION_AND_ESTATE_MANAGEMENT->value],
                ['name' => CourseEnum::WATER_RESOURCES_AND_IRRIGATION_ENGINEERING->value],
            ],
            'Construction Engineering' => [
                ['name' => CourseEnum::BUILDING_TECHNOLOGY->value],
                ['name' => CourseEnum::CARPENTRY_AND_JOINERY->value],
                ['name' => CourseEnum::CONSTRUCTION_ENGINEERING->value],
                ['name' => CourseEnum::PAINTING_AND_DECORATING->value],
                ['name' => CourseEnum::PLUMBING_AND_DRAIN_LAYING->value],
            ],
            'Electrical Engineering' => [
                ['name' => CourseEnum::COMPUTER_SYSTEMS->value],
                ['name' => CourseEnum::DOMESTIC_AND_INDUSTRIAL_SOLAR_INSTALLATION->value],
                ['name' => CourseEnum::ELECTRICAL_POWER_ENGINEERING->value],
                ['name' => CourseEnum::ELECTRONIC_COMMUNICATION_SYSTEMS->value],
                ['name' => CourseEnum::INSTRUMENTATION_AND_CONTROL_SYSTEMS->value],
                ['name' => CourseEnum::MICROWAVE_AND_RADAR->value],
                ['name' => CourseEnum::MOBILE_AND_SATELLITE_COMMUNICATION->value],
            ],
            'Information Technology' => [
                ['name' => CourseEnum::IT->value],
                ['name' => CourseEnum::PROFESSIONAL_COMPUTER_ENGINEERING->value],
                ['name' => CourseEnum::PROFESSIONAL_COMPUTING_AND_INFORMATION_SYSTEMS->value],
            ],
            'Library and Information Systems' => [
                ['name' => CourseEnum::LIBRARY_AND_INFORMATION_SCIENCES->value],
                ['name' => CourseEnum::RECORDS_MANAGEMENT_AND_INFORMATION_SCIENCES->value],
            ],
            'Mechanical and Production Engineering' => [
                ['name' => CourseEnum::DRAUGHTING_AND_DESIGN_TECHNOLOGY->value],
                ['name' => CourseEnum::FABRICATION_ENGINEERING->value],
                ['name' => CourseEnum::MACHINE_SHOP_ENGINEERING->value],
                ['name' => CourseEnum::MECHANICAL_ENGINEERING->value],
                ['name' => CourseEnum::MILLWRIGHT_WORKS->value],
                ['name' => CourseEnum::PLANT_ENGINEERING->value],
                ['name' => CourseEnum::PRODUCTION_ENGINEERING->value],
                ['name' => CourseEnum::REFRIGERATION_AND_AIR_CONDITIONING->value],
                ['name' => CourseEnum::VEHICLE_BODY_BUILDING->value],
            ],
            'Printing and Graphic arts' => [
                ['name' => CourseEnum::APPLIED_ART_AND_DESIGN->value],
                ['name' => CourseEnum::DESIGN_FOR_PRINT->value],
                ['name' => CourseEnum::FINE_ARTS->value],
                ['name' => CourseEnum::MACHINE_PRINTING->value],
                ['name' => CourseEnum::MULTIMEDIA->value],
                ['name' => CourseEnum::PACKAGING_MACHINE_MINDING->value],
                ['name' => CourseEnum::PHOTOGRAPHY->value],
                ['name' => CourseEnum::PRINTING_FINISHING_AND_CONVERTING->value],
                ['name' => CourseEnum::PRINT_FINISHING_TECHNOLOGY->value],
                ['name' => CourseEnum::PRINT_PRODUCTION_TECHNOLOGY->value],
                ['name' => CourseEnum::PRINT_ORIGINATION_TECHNOLOGY->value],
            ],
            'Mass Communication' => [
                ['name' => CourseEnum::BROADCAST_JOURNALISM->value],
                ['name' => CourseEnum::MASS_COMMUNICATION->value],
                ['name' => CourseEnum::PRINT_JOURNALISM->value],
                ['name' => CourseEnum::PUBLIC_RELATIONS->value],
            ],
            'Office Management' => [
                ['name' => CourseEnum::OFFICE_MANAGEMENT->value],
            ],
            'Tourism and Hospitality' => [
                ['name' => CourseEnum::BAKERY_TECHNOLOGY_AND_MANAGEMENT->value],
                ['name' => CourseEnum::CULINARY_ARTS->value],
                ['name' => CourseEnum::PROFESSIONAL_COOKERY->value],
                ['name' => CourseEnum::TOURISM_AND_HOSPITALITY_MANAGEMENT->value],
            ]
        ];

        foreach ($courses as $key => $rows) {
            foreach ($rows as $row) {
                $exist = Course::where('name', $row['name'])->first();
                if (!$exist instanceof Course) {
                    $row['description'] = $key;
                    Course::create($row);
                }
            }
        }
    }
}
