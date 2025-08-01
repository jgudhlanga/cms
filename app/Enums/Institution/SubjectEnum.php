<?php

namespace App\Enums\Institution;

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

    public function id(): string
    {
        return match ($this) {
            self::ACCOUNTING => 1,
            self::AGRICULTURE => 2,
            self::ART => 3,
            self::BIBLE_KNOWLEDGE => 4,
            self::BUILDING_STUDIES => 5,
            self::BUSINESS_AND_ENTERPRISE_SKILLS => 6,
            self::BUSINESS_STUDIES => 7,
            self::CHINESE => 8,
            self::COMMERCE => 9,
            self::COMPUTER_SCIENCE => 10,
            self::DESIGN_AND_TECHNOLOGY => 11,
            self::ECONOMICS => 12,
            self::ENGLISH => 13,
            self::FASHION_AND_FABRICS => 14,
            self::FOOD_AND_NUTRITION => 15,
            self::FRENCH => 16,
            self::GEOGRAPHY => 17,
            self::GERMAN => 18,
            self::HISTORY => 19,
            self::INTEGRATED_SCIENCE => 20,
            self::LITERATURE_IN_ENGLISH => 21,
            self::MATHEMATICS => 22,
            self::METAL_TECHNOLOGY_AND_DESIGN => 23,
            self::MUSIC => 24,
            self::NDEBELE => 25,
            self::PHYSICAL_EDUCATION => 26,
            self::RELIGIOUS_STUDIES => 27,
            self::SHONA => 28,
            self::SPANISH => 29,
            self::TECHNICAL_GRAPHICS => 30,
            self::WOOD_TECHNOLOGY_AND_DESIGN => 31,

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
