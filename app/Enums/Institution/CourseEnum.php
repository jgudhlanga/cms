<?php

namespace App\Enums\Institution;

enum CourseEnum: string
{
    # APPLIED ARTS
    case BEAUTY_THERAPY = "Beauty Therapy";
    case COSMETOLOGY = "Cosmetology";
    case HAIRDRESSING = "Hairdressing";
    case INDUSTRIAL_CLOTHING_DESIGN_AND_CONSTRUCTION = "Industrial Clothing Design and Construction Design";

    # APPLIED SCIENCE TECHNOLOGY
    case APPLIED_BIOLOGICAL_TECHNOLOGY = "Applied Biological Technology";
    case APPLIED_CHEMICAL_TECHNOLOGY = "Applied Chemical Technology";
    case CHEMICAL_ENGINEERING = "Chemical Engineering";
    case CHEMICAL_TECHNOLOGY = "Chemical Technology";
    case FOOD_SCIENCE = "Food Science";
    case HORTICULTURE = "Horticulture";
    case LABORATORY_TECHNOLOGY = "Laboratory Technology";
    case METALLURGICAL_ASSAYING = "Metallurgical Assaying";
    case PHARMACEUTICAL_TECHNOLOGY = "Pharmaceutical Technology";
    case POLYMER_TECHNOLOGY = "Polymer Technology";

    # AUTOMOTIVE ENGINEERING
    case AUTOMOBILE_ELECTRICS_AND_ELECTRONICS = "Automobile Electrics And Electronics";
    case AUTOMOTIVE_ENGINEERING = "Automotive Engineering";
    case AUTOMOTIVE_PRECISION_MACHINING = "Automotive Precision Machining";
    case DIESEL_PLANT_FITTING = "Diesel Plant Fitting";
    case MOTOR_CYCLE_MECHANICS = "Motor Cycle Machining";
    case MOTOR_VEHICLE_BODY_REPAIRS = "Motor Vehicle Body Repairs";
    case MOTOR_VEHICLE_MECHANICS = "Motor Vehicle Mechanics";

    # BUSINESS AND MANAGEMENT STUDIES
    case ACCOUNTANCY = "Accountancy";
    case BANKING_AND_FINANCE = "Banking and Finance";
    case HEALTH_SERVICES_MANAGEMENT = "Health Services Management";
    case HUMAN_RESOURCES_MANAGEMENT = "Human Resources Management";
    case PENSIONS_AND_INVESTMENTS_MANAGEMENT = "Pensions & Investments Management";
    case PURCHASING_AND_SUPPLY_MANAGEMENT = "Purchasing & Supply Management";
    case SALES_AND_MARKETING_MANAGEMENT = "Sales & Marketing Management";
    case TRAINERS_DIPLOMA_IN_EDUCATION = "Trainers Diploma In Education";
    case TRANSPORT_AND_LOGISTICS_MANAGEMENT = "Transport & Logistics Management";

    # CIVIL ENGINEERING
    case ARCHITECTURAL_TECHNOLOGY = "Architectural Technology";
    case CARTOGRAPHY_AND_GEO_VISUALISATION_TECHNOLOGY = "Cartography & Geo-Visualization Theory Technology";
    case CIVIL_ENGINEERING = "Civil Engineering";
    case QUANTITY_SURVEYING = "Quantity Surveying";
    case SURVEYING_AND_GEOMATICS = "Surveying and Geomatics";
    case URBAN_AND_REGIONAL_PLANNING = "Urban And Regional Planning";
    case VALUATION_AND_ESTATE_MANAGEMENT = "Valuation & Estate Management";
    case WATER_RESOURCES_AND_IRRIGATION_ENGINEERING = "Water Resources & Irrigation Engineering";

    # CONSTRUCTION ENGINEERING
    case BUILDING_TECHNOLOGY = "Building Technology";
    case CARPENTRY_AND_JOINERY = "Carpentry and Joinery";
    case CONSTRUCTION_ENGINEERING = "Construction Engineering";
    case PAINTING_AND_DECORATING = "Painting and Decorating Technology";
    case PLUMBING_AND_DRAIN_LAYING = "Plumbing and Drain Laying";

    # ELECTRICAL ENGINEERING
    case COMPUTER_SYSTEMS = "Computer Systems";
    case DOMESTIC_AND_INDUSTRIAL_SOLAR_INSTALLATION = "Domestic and Industrial Solar Installation";
    case ELECTRICAL_POWER_ENGINEERING = "Electrical Power Engineering";
    case ELECTRONIC_COMMUNICATION_SYSTEMS = "Electronic Communication Systems";
    case INSTRUMENTATION_AND_CONTROL_SYSTEMS = "Instrumentation and Control Systems";
    case MICROWAVE_AND_RADAR = "Microwave and Radar";
    case MOBILE_AND_SATELLITE_COMMUNICATION = "Mobile and Satellite Communication";

    # ICT
    case IT = "Information Technology";
    case PROFESSIONAL_COMPUTER_ENGINEERING = "Professional Computer Engineering";
    case PROFESSIONAL_COMPUTING_AND_INFORMATION_SYSTEMS = "Professional Computing and Information Systems";

    #LIBRARY AND INFORMATION SCIENCES
    case LIBRARY_AND_INFORMATION_SCIENCES = "Library and Information Sciences";
    case RECORDS_MANAGEMENT_AND_INFORMATION_SCIENCES = "Records Management and Information Sciences";

    # MECHANICAL AND PRODUCTION ENGINEERING
    case DRAUGHTING_AND_DESIGN_TECHNOLOGY = "Draughting and Design Technology";
    case FABRICATION_ENGINEERING = "Fabrication Engineering";
    case MACHINE_SHOP_ENGINEERING = "Machine Shop Engineering";
    case MECHANICAL_ENGINEERING = "Mechanical Engineering";
    case MILLWRIGHT_WORKS = "Millwright Works";
    case PLANT_ENGINEERING = "Plant Engineering";
    case PRODUCTION_ENGINEERING = "Production Engineering";
    case REFRIGERATION_AND_AIR_CONDITIONING = "Refrigeration and Air Conditioning";
    case VEHICLE_BODY_BUILDING = "Vehicle Body Building";

    # PRINTING AND GRAPHIC ARTS
    case APPLIED_ART_AND_DESIGN = "Applied Art and Design";
    case DESIGN_FOR_PRINT = "Design For Print";
    case FINE_ARTS = "Fine Arts";
    case MACHINE_PRINTING = "Machine Printing";
    case MULTIMEDIA = "Multimedia";
    case PACKAGING_MACHINE_MINDING = "Packaging Machine Minding";
    case PHOTOGRAPHY = "Photography";
    case PRINTING_FINISHING_AND_CONVERTING = "Printing, Finishing and Converting";
    case PRINT_FINISHING_TECHNOLOGY = "Print Finishing Technology";
    case PRINT_PRODUCTION_TECHNOLOGY = "Print Production Technology";
    case PRINT_ORIGINATION_TECHNOLOGY = "Print Origination Technology";

    # MASS COMMUNICATION
    case BROADCAST_JOURNALISM = "Broadcast Journalism";
    case MASS_COMMUNICATION = "Mass Communication";
    case PRINT_JOURNALISM = "Print Journalism";
    case PUBLIC_RELATIONS = "Public Relations";

    # OFFICE MANAGEMENT
    case OFFICE_MANAGEMENT = "Office Management";

    # TOURISM AND HOSPITALITY
    case BAKERY_TECHNOLOGY_AND_MANAGEMENT = "Bakery Technology and Management";
    case CULINARY_ARTS = "Culinary Arts";
    case PROFESSIONAL_COOKERY = "Professional Cookery";
    case TOURISM_AND_HOSPITALITY_MANAGEMENT = "Tourism and Hospitality Management";

    public function label(): string
    {
        return match ($this) {
            # APPLIED ARTS
            self::BEAUTY_THERAPY => "Beauty Therapy",
            self::COSMETOLOGY => "Cosmetology",
            self::HAIRDRESSING => "Hairdressing",
            self::INDUSTRIAL_CLOTHING_DESIGN_AND_CONSTRUCTION => "Industrial Clothing Design and Construction Design",
            # APPLIED SCIENCE TECHNOLOGY
            self::APPLIED_BIOLOGICAL_TECHNOLOGY => "Applied Biological Technology",
            self::APPLIED_CHEMICAL_TECHNOLOGY => "Applied Chemical Technology",
            self::CHEMICAL_ENGINEERING => "Chemical Engineering",
            self::CHEMICAL_TECHNOLOGY => "Chemical Technology",
            self::FOOD_SCIENCE => "Food Science",
            self::HORTICULTURE => "Horticulture",
            self::LABORATORY_TECHNOLOGY => "Laboratory Technology",
            self::METALLURGICAL_ASSAYING => "Metallurgical Assaying",
            self::PHARMACEUTICAL_TECHNOLOGY => "Pharmaceutical Technology",
            self::POLYMER_TECHNOLOGY => "Polymer Technology",
            # AUTOMOTIVE ENGINEERING
            self::AUTOMOBILE_ELECTRICS_AND_ELECTRONICS => "Automobile Electrics And Electronics",
            self::AUTOMOTIVE_ENGINEERING => "Automotive Engineering",
            self::AUTOMOTIVE_PRECISION_MACHINING => "Automotive Precision Machining",
            self::DIESEL_PLANT_FITTING => "Diesel Plant Fitting",
            self::MOTOR_CYCLE_MECHANICS => "Motor Cycle Machining",
            self::MOTOR_VEHICLE_BODY_REPAIRS => "Motor Vehicle Body Repairs",
            self::MOTOR_VEHICLE_MECHANICS => "Motor Vehicle Mechanics",
            # ICT
            self::IT => "IT",
            self::PROFESSIONAL_COMPUTER_ENGINEERING => "Professional Computer Engineering",
            self::PROFESSIONAL_COMPUTING_AND_INFORMATION_SYSTEMS => "Professional Computing and Information Systems",
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
            self::ARCHITECTURAL_TECHNOLOGY => "Architectural Technology",
            self::CARTOGRAPHY_AND_GEO_VISUALISATION_TECHNOLOGY => "Cartography & Geo-Visualization Theory Technology",
            self::CIVIL_ENGINEERING => "Civil Engineering",
            self::QUANTITY_SURVEYING => "Quantity Surveying",
            self::SURVEYING_AND_GEOMATICS => "Surveying and Geomatics",
            self::URBAN_AND_REGIONAL_PLANNING => "Urban And Regional Planning",
            self::VALUATION_AND_ESTATE_MANAGEMENT => "Valuation & Estate Management",
            self::WATER_RESOURCES_AND_IRRIGATION_ENGINEERING => "Water Resources & Irrigation Engineering",
            # CONSTRUCTION ENGINEERING
            self::BUILDING_TECHNOLOGY => "Building Technology",
            self::CARPENTRY_AND_JOINERY => "Carpentry and Joinery",
            self::CONSTRUCTION_ENGINEERING => "Construction Engineering",
            self::PAINTING_AND_DECORATING => "Painting and Decorating Technology",
            self::PLUMBING_AND_DRAIN_LAYING => "Plumbing and Drain Laying",
            # ELECTRICAL ENGINEERING
            self::COMPUTER_SYSTEMS => "Computer Systems",
            self::DOMESTIC_AND_INDUSTRIAL_SOLAR_INSTALLATION => "Domestic and Industrial Solar Installation",
            self::ELECTRONIC_COMMUNICATION_SYSTEMS => "Electronic Communication Systems",
            self::ELECTRICAL_POWER_ENGINEERING => "Electrical Power Engineering",
            self::INSTRUMENTATION_AND_CONTROL_SYSTEMS => "Instrumentation and Control Systems",
            self::MICROWAVE_AND_RADAR => "Microwave and Radar",
            self::MOBILE_AND_SATELLITE_COMMUNICATION => "Mobile and Satellite Communication",
            #LIBRARY AND INFORMATION SCIENCES
            self::LIBRARY_AND_INFORMATION_SCIENCES => "Library and Information Sciences",
            self::RECORDS_MANAGEMENT_AND_INFORMATION_SCIENCES => "Records Management and Information Sciences",
            # MECHANICAL AND PRODUCTION ENGINEERING
            self::DRAUGHTING_AND_DESIGN_TECHNOLOGY => "Draughting and Design Technology",
            self::FABRICATION_ENGINEERING => "Fabrication Engineering",
            self::MACHINE_SHOP_ENGINEERING => "Machine Shop Engineering",
            self::MECHANICAL_ENGINEERING => "Mechanical Engineering",
            self::MILLWRIGHT_WORKS => "Millwright Works",
            self::PLANT_ENGINEERING => "Plant Engineering",
            self::PRODUCTION_ENGINEERING => "Production Engineering",
            self::REFRIGERATION_AND_AIR_CONDITIONING => "Refrigeration and Air Conditioning",
            self::VEHICLE_BODY_BUILDING => "Vehicle Body Building",
            # PRINTING AND GRAPHIC ARTS
            self::APPLIED_ART_AND_DESIGN => "Applied Art and Design",
            self::DESIGN_FOR_PRINT => "Design For Print",
            self::FINE_ARTS => "Fine Arts",
            self::MACHINE_PRINTING => "Machine Printing",
            self::MULTIMEDIA => "Multimedia",
            self::PACKAGING_MACHINE_MINDING => "Packaging Machine Minding",
            self::PHOTOGRAPHY => "Photography",
            self::PRINTING_FINISHING_AND_CONVERTING => "Printing, Finishing and Converting",
            self::PRINT_FINISHING_TECHNOLOGY => "Print Finishing Technology",
            self::PRINT_ORIGINATION_TECHNOLOGY => "Print Origination Technology",
            self::PRINT_PRODUCTION_TECHNOLOGY => "Print Production Technology",
            # MASS COMMUNICATION
            self::BROADCAST_JOURNALISM => "Broadcast Journalism",
            self::MASS_COMMUNICATION => "Mass Communication",
            self::PRINT_JOURNALISM => "Print Journalism",
            self::PUBLIC_RELATIONS => "Public Relations",
            # OFFICE MANAGEMENT
            self::OFFICE_MANAGEMENT => "Office Management",
            # TOURISM AND HOSPITALITY
            self::BAKERY_TECHNOLOGY_AND_MANAGEMENT => "Bakery Technology and Management",
            self::CULINARY_ARTS => "Culinary Arts",
            self::TOURISM_AND_HOSPITALITY_MANAGEMENT => "Tourism and Hospitality Management",
            self::PROFESSIONAL_COOKERY => "Professional Cookery",
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
