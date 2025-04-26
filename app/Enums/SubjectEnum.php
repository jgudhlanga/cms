<?php

namespace App\Enums;

enum SubjectEnum: string
{

    case ACCOUNTING = "Accounts";
    case AGRICULTURE = "Agriculture";
    case ART = "Art";
    case BIBLE_KNOWLEDGE = "Bible Knowledge";
    case BUILDING_STUDIES = "Building Studies";
    case BUSINESS_AND_ENTERPRISE_SKILLS = "Business and Enterprise Skills";
    case BUSINESS_STUDIES = "Business Studies";
    case CHINESE = "Chinese";
    case COMMERCE = "Commerce";
    case COMPUTER_SCIENCE = "Computer Science";
    case DESIGN_AND_TECHNOLOGY = "Design and Technology";
    case ECONOMICS = "Economics";
    case ENGLISH = "English";
    case FASHION_AND_FABRICS = "Fashion and Fabrics";
    case FOOD_AND_NUTRITION = "Food and Nutrition";
    case FRENCH = "French";
    case GEOGRAPHY = "Geography";
    case GERMAN = "German";
    case HISTORY = "History";
    case INTEGRATED_SCIENCE = "Integrated Science";
    case LITERATURE_IN_ENGLISH = "Literature in English";
    case MATHEMATICS = "Mathematics";
    case METAL_TECHNOLOGY_AND_DESIGN = "Metal Technology and Design";
    case MUSIC = "Music";
    case NDEBELE = "Ndebele";
    case PHYSICAL_EDUCATION = "Physical Education, Sport and Mass Displays";
    case RELIGIOUS_STUDIES = "Religious Studies";
    case SHONA = "Shona";
    case SPANISH = "Spanish";
    case TECHNICAL_GRAPHICS = "Technical Graphics";
    case WOOD_TECHNOLOGY_AND_DESIGN = "Wood Technology and Design";


    public function label(): string
    {
        return match ($this) {
            self::ACCOUNTING => "Accounts",
            self::AGRICULTURE => "Agriculture",
            self::ART => "Art",
            self::BIBLE_KNOWLEDGE => "Bible Knowledge",
            self::BUILDING_STUDIES => "Building Studies",
            self::BUSINESS_AND_ENTERPRISE_SKILLS => "Business and Enterprise Skills",
            self::BUSINESS_STUDIES => "Business Studies",
            self::CHINESE => "Chinese",
            self::COMMERCE => "Commerce",
            self::COMPUTER_SCIENCE => "Computer Science",
            self::DESIGN_AND_TECHNOLOGY => "Design and Technology",
            self::ECONOMICS => "Economics",
            self::ENGLISH => "English",
            self::FASHION_AND_FABRICS => "Fashion and Fabrics",
            self::FOOD_AND_NUTRITION => "Food and Nutrition",
            self::FRENCH => "French",
            self::GEOGRAPHY => "Geography",
            self::GERMAN => "German",
            self::HISTORY => "History",
            self::INTEGRATED_SCIENCE => "Integrated Science",
            self::LITERATURE_IN_ENGLISH => "Literature in English",
            self::MATHEMATICS => "Mathematics",
            self::METAL_TECHNOLOGY_AND_DESIGN => "Metal Technology and Design",
            self::MUSIC => "Music",
            self::NDEBELE => "Ndebele",
            self::PHYSICAL_EDUCATION => "Physical Education, Sport and Mass Displays",
            self::RELIGIOUS_STUDIES => "Religious Studies",
            self::SHONA => "Shona",
            self::SPANISH => "Spanish",
            self::TECHNICAL_GRAPHICS => "Technical Graphics",
            self::WOOD_TECHNOLOGY_AND_DESIGN => "Wood Technology and Design",

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
