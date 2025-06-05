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
            'Applied Arts' => [
                ['name' => CourseEnum::BEAUTY_THERAPY->value, 'position' => 1],
                ['name' => CourseEnum::COSMETOLOGY->value, 'position' => 2],
                ['name' => CourseEnum::HAIRDRESSING->value, 'position' => 3],
                ['name' => CourseEnum::INDUSTRIAL_CLOTHING_DESIGN_AND_CONSTRUCTION->value, 'position' => 4],
            ],
            'Applied Science Technology' => [
                ['name' => CourseEnum::APPLIED_BIOLOGICAL_TECHNOLOGY->value, 'position' => 5],
                ['name' => CourseEnum::APPLIED_CHEMICAL_TECHNOLOGY->value, 'position' => 6],
                ['name' => CourseEnum::CHEMICAL_ENGINEERING->value, 'position' => 7],
                ['name' => CourseEnum::CHEMICAL_TECHNOLOGY->value, 'position' => 8],
                ['name' => CourseEnum::FOOD_SCIENCE->value, 'position' => 9],
                ['name' => CourseEnum::HORTICULTURE->value, 'position' => 10],
                ['name' => CourseEnum::LABORATORY_TECHNOLOGY->value, 'position' => 11],
                ['name' => CourseEnum::METALLURGICAL_ASSAYING->value, 'position' => 12],
                ['name' => CourseEnum::PHARMACEUTICAL_TECHNOLOGY->value, 'position' => 13],
                ['name' => CourseEnum::POLYMER_TECHNOLOGY->value, 'position' => 14],
            ],
            'Automotive' => [
                ['name' => CourseEnum::AUTOMOBILE_ELECTRICS_AND_ELECTRONICS->value, 'position' => 15],
                ['name' => CourseEnum::AUTOMOTIVE_ENGINEERING->value, 'position' => 16],
                ['name' => CourseEnum::AUTOMOTIVE_PRECISION_MACHINING->value, 'position' => 17],
                ['name' => CourseEnum::DIESEL_PLANT_FITTING->value, 'position' => 18],
                ['name' => CourseEnum::MOTOR_CYCLE_MECHANICS->value, 'position' => 19],
                ['name' => CourseEnum::MOTOR_VEHICLE_BODY_REPAIRS->value, 'position' => 20],
                ['name' => CourseEnum::MOTOR_VEHICLE_MECHANICS->value, 'position' => 21],
            ],
            'Commerce' => [
                ['name' => CourseEnum::ACCOUNTANCY->value, 'position' => 22],
                ['name' => CourseEnum::BANKING_AND_FINANCE->value, 'position' => 23],
                ['name' => CourseEnum::HEALTH_SERVICES_MANAGEMENT->value, 'position' => 24],
                ['name' => CourseEnum::HUMAN_RESOURCES_MANAGEMENT->value, 'position' => 25],
                ['name' => CourseEnum::PENSIONS_AND_INVESTMENTS_MANAGEMENT->value, 'position' => 26],
                ['name' => CourseEnum::PURCHASING_AND_SUPPLY_MANAGEMENT->value, 'position' => 27],
                ['name' => CourseEnum::SALES_AND_MARKETING_MANAGEMENT->value, 'position' => 28],
                ['name' => CourseEnum::TRAINERS_DIPLOMA_IN_EDUCATION->value, 'position' => 29],
                ['name' => CourseEnum::TRANSPORT_AND_LOGISTICS_MANAGEMENT->value, 'position' => 30],
            ],
            'Civil Engineering' => [
                ['name' => CourseEnum::ARCHITECTURAL_TECHNOLOGY->value, 'position' => 31],
                ['name' => CourseEnum::CARTOGRAPHY_AND_GEO_VISUALISATION_TECHNOLOGY->value, 'position' => 32],
                ['name' => CourseEnum::CIVIL_ENGINEERING->value, 'position' => 33],
                ['name' => CourseEnum::QUANTITY_SURVEYING->value, 'position' => 34],
                ['name' => CourseEnum::SURVEYING_AND_GEOMATICS->value, 'position' => 35],
                ['name' => CourseEnum::URBAN_AND_REGIONAL_PLANNING->value, 'position' => 36],
                ['name' => CourseEnum::VALUATION_AND_ESTATE_MANAGEMENT->value, 'position' => 37],
                ['name' => CourseEnum::WATER_RESOURCES_AND_IRRIGATION_ENGINEERING->value, 'position' => 38],
            ],
            'Construction Engineering' => [
                ['name' => CourseEnum::BUILDING_TECHNOLOGY->value, 'position' => 39],
                ['name' => CourseEnum::CARPENTRY_AND_JOINERY->value, 'position' => 40],
                ['name' => CourseEnum::CONSTRUCTION_ENGINEERING->value, 'position' => 41],
                ['name' => CourseEnum::PAINTING_AND_DECORATING->value, 'position' => 42],
                ['name' => CourseEnum::PLUMBING_AND_DRAIN_LAYING->value, 'position' => 43],
            ],
            'Electrical Engineering' => [
                ['name' => CourseEnum::COMPUTER_SYSTEMS->value, 'position' => 44],
                ['name' => CourseEnum::DOMESTIC_AND_INDUSTRIAL_SOLAR_INSTALLATION->value, 'position' => 45],
                ['name' => CourseEnum::ELECTRICAL_POWER_ENGINEERING->value, 'position' => 46],
                ['name' => CourseEnum::ELECTRONIC_COMMUNICATION_SYSTEMS->value, 'position' => 47],
                ['name' => CourseEnum::INSTRUMENTATION_AND_CONTROL_SYSTEMS->value, 'position' => 48],
                ['name' => CourseEnum::MICROWAVE_AND_RADAR->value, 'position' => 49],
                ['name' => CourseEnum::MOBILE_AND_SATELLITE_COMMUNICATION->value, 'position' => 50],
            ],
            'Information Technology' => [
                ['name' => CourseEnum::IT->value, 'position' => 51],
                ['name' => CourseEnum::PROFESSIONAL_COMPUTER_ENGINEERING->value, 'position' => 52],
                ['name' => CourseEnum::PROFESSIONAL_COMPUTING_AND_INFORMATION_SYSTEMS->value, 'position' => 53],
            ],
            'Library and Information Systems' => [
                ['name' => CourseEnum::LIBRARY_AND_INFORMATION_SCIENCES->value, 'position' => 54],
                ['name' => CourseEnum::RECORDS_MANAGEMENT_AND_INFORMATION_SCIENCES->value, 'position' => 55],
            ],
            'Mechanical and Production Engineering' => [
                ['name' => CourseEnum::DRAUGHTING_AND_DESIGN_TECHNOLOGY->value, 'position' => 56],
                ['name' => CourseEnum::FABRICATION_ENGINEERING->value, 'position' => 57],
                ['name' => CourseEnum::MACHINE_SHOP_ENGINEERING->value, 'position' => 58],
                ['name' => CourseEnum::MECHANICAL_ENGINEERING->value, 'position' => 59],
                ['name' => CourseEnum::MILLWRIGHT_WORKS->value, 'position' => 60],
                ['name' => CourseEnum::PLANT_ENGINEERING->value, 'position' => 61],
                ['name' => CourseEnum::PRODUCTION_ENGINEERING->value, 'position' => 62],
                ['name' => CourseEnum::REFRIGERATION_AND_AIR_CONDITIONING->value, 'position' => 63],
                ['name' => CourseEnum::VEHICLE_BODY_BUILDING->value, 'position' => 64],
            ],
            'Printing and Graphic Arts' => [
                ['name' => CourseEnum::APPLIED_ART_AND_DESIGN->value, 'position' => 65],
                ['name' => CourseEnum::DESIGN_FOR_PRINT->value, 'position' => 66],
                ['name' => CourseEnum::FINE_ARTS->value, 'position' => 67],
                ['name' => CourseEnum::MACHINE_PRINTING->value, 'position' => 68],
                ['name' => CourseEnum::MULTIMEDIA->value, 'position' => 69],
                ['name' => CourseEnum::PACKAGING_MACHINE_MINDING->value, 'position' => 70],
                ['name' => CourseEnum::PHOTOGRAPHY->value, 'position' => 71],
                ['name' => CourseEnum::PRINTING_FINISHING_AND_CONVERTING->value, 'position' => 72],
                ['name' => CourseEnum::PRINT_FINISHING_TECHNOLOGY->value, 'position' => 73],
                ['name' => CourseEnum::PRINT_PRODUCTION_TECHNOLOGY->value, 'position' => 74],
                ['name' => CourseEnum::PRINT_ORIGINATION_TECHNOLOGY->value, 'position' => 75],
            ],
            'Mass Communication' => [
                ['name' => CourseEnum::BROADCAST_JOURNALISM->value, 'position' => 76],
                ['name' => CourseEnum::MASS_COMMUNICATION->value, 'position' => 77],
                ['name' => CourseEnum::PRINT_JOURNALISM->value, 'position' => 78],
                ['name' => CourseEnum::PUBLIC_RELATIONS->value, 'position' => 79],
            ],
            'Office Management' => [
                ['name' => CourseEnum::OFFICE_MANAGEMENT->value, 'position' => 80],
            ],
            'Tourism and Hospitality' => [
                ['name' => CourseEnum::BAKERY_TECHNOLOGY_AND_MANAGEMENT->value, 'position' => 81],
                ['name' => CourseEnum::CULINARY_ARTS->value, 'position' => 82],
                ['name' => CourseEnum::PROFESSIONAL_COOKERY->value, 'position' => 83],
                ['name' => CourseEnum::TOURISM_AND_HOSPITALITY_MANAGEMENT->value, 'position' => 84],
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
