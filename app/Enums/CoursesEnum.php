<?php

namespace App\Enums;

enum CoursesEnum: string
{
    # ICT
    case COMPUTER_ENGINEERING = "Computer Engineering";
    case COMPUTING_AND_INFORMATION_SYSTEMS = "Computing and Information Systems";
    case IT = "IT";

    # APPLIED ARTS
    case BEAUTY_THERAPY = "Beauty Therapy";
    case HAIRDRESSING = "Hairdressing";
    case INDUSTRIAL_CLOTHING_DESIGN_AND_CONSTRUCTION = "Industrial Clothing Design and Construction Design";
    case COSMETOLOGY = "Cosmetology";

    # AUTOMOTIVE ENG
    case MOTOR_VEHICLE_MECHANICS = "Motor Vehicle Mechanics";
    case AUTOMOTIVE_PRECISION_MACHINING = "Automotive Precision Machining";
    case MOTOR_CYCLE_MECHANICS = "Motor Cycle Machining";
    case AUTOMOBILE_ELECTRICS_AND_ELECTRONICS = "Automobile Electrics And Electronics";
    case MOTOR_VEHICLE_BODY_REPAIRS = "Motor Vehicle Body Repairs";
    case DIESEL_PLANT_FITTING = "Diesel Plant Fitting";
    case AUTOMOTIVE_ENGINEERING = "Automotive Engineering";

    # BUSINESS AND MANAGEMENT STUDIES
    case ACCOUNTANCY = "Accountancy";
    case BANKING_AND_FINANCE = "Banking and Finance";
    case HEALTH_SERVICES_MANAGEMENT = "Health Services Management";
    case HUMAN_RESOURCES_MANAGEMENT = "Human Resources Management";
    case PENSIONS_AND_INVESTMENTS_MANAGEMENT = "Pensions & Investments Management";
    case PURCHASING_AND_SUPPLY_MANAGEMENT = "Purchasing & Supply Management";
    case SALES_AND_MARKETING_MANAGEMENT = "Sales & Marketing Management";
    case TRANSPORT_AND_LOGISTICS_MANAGEMENT = "Transport & Logistics Management";
    case TRAINERS_DIPLOMA_IN_EDUCATION = "Trainers Diploma In Education";

    # CIVIL ENGINEERING
    case CIVIL_ENGINEERING = "Civil Engineering";
    case SURVEYING_AND_GEOMATICS = "Surveying and Geomatics";
    case URBAN_AND_REGIONAL_PLANNING = "Urban And Regional Planning";
    case QUANTITY_SURVEYING = "Quantity Surveying";
    case ARCHITECTURAL_TECHNOLOGY = "Architectural Technology";
    case CARTOGRAPHY_AND_GEO_VISUALISATION_TECHNOLOGY = "Cartography & Geo-Visualization Theory Technology";
    case VALUATION_AND_ESTATE_MANAGEMENT = "Valuation & Estate Management";
    case WATER_RESOURCES_AND_IRRIGATION_ENGINEERING = "Water Resources & Irrigation Engineering";

    # CONSTRUCTION ENGINEERING
    case CARPENTRY_AND_JOINERY = "Carpentry and Joinery";
    case BUILDING_TECHNOLOGY = "Building Technology";
    case PAINTING_AND_DECORATING = "Painting and Decorating Technology";
    case PLUMBING_AND_DRAIN_LAYING = "Plumbing and Drain Laying";
    case CONSTRUCTION_ENGINEERING = "Construction Engineering";

    # ELECTRICAL ENGINEERING
    case ELECTRONIC_COMMUNICATION_SYSTEMS = "Electronic Communication Systems";
    case COMPUTER_SYSTEMS = "Computer Systems";
    case INSTRUMENTATION_AND_CONTROL_SYSTEMS = "Instrumentation and Control Systems";
    case ELECTRICAL_POWER_ENGINEERING = "Electrical Power Engineering";
    case MOBILE_AND_SATELLITE_COMMUNICATION = "Mobile and Satellite Communication";
    case MICROWAVE_AND_RADAR = "Microwave and Radar";
    case DOMESTIC_AND_INDUSTRIAL_SOLAR_INSTALLATION = "Domestic and Industrial Solar Installation";

    #LIBRARY AND INFORMATION SCIENCES
    case LIBRARY_AND_INFORMATION_SCIENCES = "Library and Information Sciences";
    case RECORDS_MANAGEMENT_AND_INFORMATION_SCIENCES = "Records Management and Information Sciences";

    # MECHANICAL AND PRODUCTION ENGINEERING
    case REFRIGERATION_AND_AIR_CONDITIONING = "Refrigeration and Air Conditioning";
    case DRAUGHTING_AND_DESIGN_TECHNOLOGY = "Draughting and Design Technology";
    case FABRICATION_ENGINEERING = "Fabrication Engineering";
    case VEHICLE_BODY_BUILDING = "Vehicle Body Building";
    case MACHINE_SHOP_ENGINEERING = "Machine Shop Engineering";
    case MILLWRIGHT_WORKS = "Millwright Works";
    case PRODUCTION_ENGINEERING = "Production Engineering";
    case PLANT_ENGINEERING = "Plant Engineering";
    case MECHANICAL_ENGINEERING = "Mechanical Engineering";

    # PRINTING AND GRAPHIC ARTS
    case MACHINE_PRINTING = "Machine Printing";
    case PACKAGING_MACHINE_MINDING = "Packaging Machine Minding";
    case PHOTOGRAPHY = "Photography";
    case APPLIED_ART_AND_DESIGN = "Applied Art and Design";
    case PRINTING_FINISHING_AND_CONVERTING = "Printing, Finishing and Converting";
    case PRINT_PRODUCTION_TECHNOLOGY = "Print Production Technology";
    case PRINT_FINISHING_TECHNOLOGY = "Print Finishing Technology";
    case PRINT_ORIGINATION_TECHNOLOGY = "Print Origination Technology";
    case DESIGN_FOR_PRINT = "Design For Print";
    case FINE_ARTS = "Fine Arts";
    case MULTIMEDIA = "Multimedia";

    # SCIENCE TECHNOLOGY
    case LABORATORY_TECHNOLOGY = "Laboratory Technology";
    case CHEMICAL_ENGINEERING = "Chemical Engineering";
    case POLYMER_TECHNOLOGY = "Polymer Technology";
    case HORTICULTURE = "Horticulture";
    case METALLURGICAL_ASSAYING = "Metallurgical Assaying";
    case PHARMACEUTICAL_TECHNOLOGY = "Pharmaceutical Technology";
    case FOOD_SCIENCE = "Food Science";
    case APPLIED_BIOLOGICAL_TECHNOLOGY = "Applied Biological Technology";
    case APPLIED_CHEMICAL_TECHNOLOGY = "Applied Chemical Technology";
    case CHEMICAL_TECHNOLOGY = "Chemical Technology";

    # MASS COMMUNICATION
    case MASS_COMMUNICATION = "Mass Communication";
    case PUBLIC_RELATIONS = "Public Relations";
    case BROADCAST_JOURNALISM = "Broadcast Journalism";
    case PRINT_JOURNALISM = "Print Journalism";

    # OFFICE MANAGEMENT
    case OFFICE_MANAGEMENT = "Office Management";

    # TOURISM AND HOSPITALITY
    case TOURISM_AND_HOSPITALITY_MANAGEMENT = "Tourism and Hospitality Management";
    case PROFESSIONAL_COOKERY = "Professional Cookery";
    case BAKERY_TECHNOLOGY_AND_MANAGEMENT = "Bakery Technology and Management";
    case CULINARY_ARTS = "Cultural Arts";

    public function label(): string
    {
        return match ($this) {
            # ICT
            self::COMPUTER_ENGINEERING => "Computer Engineering",
            self::COMPUTING_AND_INFORMATION_SYSTEMS => "Computing and Information Systems",
            self::IT => "IT",
            # APPLIED ARTS
            self::BEAUTY_THERAPY => "Beauty Therapy",
            self::HAIRDRESSING => "Hairdressing",
            self::INDUSTRIAL_CLOTHING_DESIGN_AND_CONSTRUCTION => "Industrial Clothing Design and Construction Design",
            self::COSMETOLOGY => "Cosmetology",
            # AUTOMOTIVE ENG
            self::MOTOR_VEHICLE_MECHANICS => "Motor Vehicle Mechanics",
            self::AUTOMOTIVE_PRECISION_MACHINING => "Automotive Precision Machining",
            self::MOTOR_CYCLE_MECHANICS => "Motor Cycle Machining",
            self::AUTOMOBILE_ELECTRICS_AND_ELECTRONICS => "Automobile Electrics And Electronics",
            self::MOTOR_VEHICLE_BODY_REPAIRS => "Motor Vehicle Body Repairs",
            self::DIESEL_PLANT_FITTING => "Diesel Plant Fitting",
            self::AUTOMOTIVE_ENGINEERING => "Automotive Engineering",
            # BUSINESS AND MANAGEMENT STUDIES
            self::ACCOUNTANCY => "Accountancy",
            self::BANKING_AND_FINANCE => "Banking and Finance",
            self::HEALTH_SERVICES_MANAGEMENT => "Health Services Management",
            self::HUMAN_RESOURCES_MANAGEMENT => "Human Resources Management",
            self::PENSIONS_AND_INVESTMENTS_MANAGEMENT => "Pensions & Investments Management",
            self::PURCHASING_AND_SUPPLY_MANAGEMENT => "Purchasing & Supply Management",
            self::SALES_AND_MARKETING_MANAGEMENT => "Sales & Marketing Management",
            self::TRANSPORT_AND_LOGISTICS_MANAGEMENT => "Transport & Logistics Management",
            self::TRAINERS_DIPLOMA_IN_EDUCATION => "Trainers Diploma In Education",
            # CIVIL ENGINEERING
            self::CIVIL_ENGINEERING => "Civil Engineering",
            self::SURVEYING_AND_GEOMATICS => "Surveying and Geomatics",
            self::URBAN_AND_REGIONAL_PLANNING => "Urban And Regional Planning",
            self::QUANTITY_SURVEYING => "Quantity Surveying",
            self::ARCHITECTURAL_TECHNOLOGY => "Architectural Technology",
            self::CARTOGRAPHY_AND_GEO_VISUALISATION_TECHNOLOGY => "Cartography & Geo-Visualization Theory Technology",
            self::VALUATION_AND_ESTATE_MANAGEMENT => "Valuation & Estate Management",
            self::WATER_RESOURCES_AND_IRRIGATION_ENGINEERING => "Water Resources & Irrigation Engineering",
            # CONSTRUCTION ENGINEERING
            self::CARPENTRY_AND_JOINERY => "Carpentry and Joinery",
            self::BUILDING_TECHNOLOGY => "Building Technology",
            self::PAINTING_AND_DECORATING => "Painting and Decorating Technology",
            self::PLUMBING_AND_DRAIN_LAYING => "Plumbing and Drain Laying",
            self::CONSTRUCTION_ENGINEERING => "Construction Engineering",
            # ELECTRICAL ENGINEERING
            self::ELECTRONIC_COMMUNICATION_SYSTEMS => "Electronic Communication Systems",
            self::COMPUTER_SYSTEMS => "Computer Systems",
            self::INSTRUMENTATION_AND_CONTROL_SYSTEMS => "Instrumentation and Control Systems",
            self::ELECTRICAL_POWER_ENGINEERING => "Electrical Power Engineering",
            self::MOBILE_AND_SATELLITE_COMMUNICATION => "Mobile and Satellite Communication",
            self::MICROWAVE_AND_RADAR => "Microwave and Radar",
            self::DOMESTIC_AND_INDUSTRIAL_SOLAR_INSTALLATION => "Domestic and Industrial Solar Installation",
            #LIBRARY AND INFORMATION SCIENCES
            self::LIBRARY_AND_INFORMATION_SCIENCES => "Library and Information Sciences",
            self::RECORDS_MANAGEMENT_AND_INFORMATION_SCIENCES => "Records Management and Information Sciences",
            # MECHANICAL AND PRODUCTION ENGINEERING
            self::REFRIGERATION_AND_AIR_CONDITIONING => "Refrigeration and Air Conditioning",
            self::DRAUGHTING_AND_DESIGN_TECHNOLOGY => "Draughting and Design Technology",
            self::FABRICATION_ENGINEERING => "Fabrication Engineering",
            self::VEHICLE_BODY_BUILDING => "Vehicle Body Building",
            self::MACHINE_SHOP_ENGINEERING => "Machine Shop Engineering",
            self::MILLWRIGHT_WORKS => "Millwright Works",
            self::PRODUCTION_ENGINEERING => "Production Engineering",
            self::PLANT_ENGINEERING => "Plant Engineering",
            self::MECHANICAL_ENGINEERING => "Mechanical Engineering",
            # PRINTING AND GRAPHIC ARTS
            self::MACHINE_PRINTING => "Machine Printing",
            self::PACKAGING_MACHINE_MINDING => "Packaging Machine Minding",
            self::PHOTOGRAPHY => "Photography",
            self::APPLIED_ART_AND_DESIGN => "Applied Art and Design",
            self::PRINTING_FINISHING_AND_CONVERTING => "Printing, Finishing and Converting",
            self::PRINT_PRODUCTION_TECHNOLOGY => "Print Production Technology",
            self::PRINT_FINISHING_TECHNOLOGY => "Print Finishing Technology",
            self::PRINT_ORIGINATION_TECHNOLOGY => "Print Origination Technology",
            self::DESIGN_FOR_PRINT => "Design For Print",
            self::FINE_ARTS => "Fine Arts",
            self::MULTIMEDIA => "Multimedia",
            # SCIENCE TECHNOLOGY
            self::LABORATORY_TECHNOLOGY => "Laboratory Technology",
            self::CHEMICAL_ENGINEERING => "Chemical Engineering",
            self::POLYMER_TECHNOLOGY => "Polymer Technology",
            self::HORTICULTURE => "Horticulture",
            self::METALLURGICAL_ASSAYING => "Metallurgical Assaying",
            self::PHARMACEUTICAL_TECHNOLOGY => "Pharmaceutical Technology",
            self::FOOD_SCIENCE => "Food Science",
            self::APPLIED_BIOLOGICAL_TECHNOLOGY => "Applied Biological Technology",
            self::APPLIED_CHEMICAL_TECHNOLOGY => "Applied Chemical Technology",
            self::CHEMICAL_TECHNOLOGY => "Chemical Technology",
            # MASS COMMUNICATION
            self::MASS_COMMUNICATION => "Mass Communication",
            self::PUBLIC_RELATIONS => "Public Relations",
            self::BROADCAST_JOURNALISM => "Broadcast Journalism",
            self::PRINT_JOURNALISM => "Print Journalism",
            # OFFICE MANAGEMENT
            self::OFFICE_MANAGEMENT => "Office Management",
            # TOURISM AND HOSPITALITY
            self::TOURISM_AND_HOSPITALITY_MANAGEMENT => "Tourism and Hospitality Management",
            self::PROFESSIONAL_COOKERY => "Professional Cookery",
            self::BAKERY_TECHNOLOGY_AND_MANAGEMENT => "Bakery Technology and Management",
            self::CULINARY_ARTS => "Cultural Arts",
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
