<?php

namespace App\Enums;

enum DistrictEnum: string
{
    # BULAWAYO
    case BULAWAYO = 'Bulawayo';

    # HARARE
    case HARARE = 'Harare';
    case CHITUNGWIZA = "Chitungwiza";

    # MANICALAND
    case BUHERA = "Buhera";
    case CHIMANIMANI = "Chimanimani";
    case CHIPINGE = "Chipinge";
    case MAKONI = "Makoni";
    case MUTARE = "Mutare";
    case MUTASA = "Mutasa";
    case NYANGA = "Nyanga";

    # MASHONALAND CENTRAL
    case BINDURA = "Bindura";
    case GURUVE = "Guruve";
    case MAZOWE = "Mazowe";
    case MBIRE = "Mbire";
    case MOUNT_DARWIN = "Mount Darwin";
    case MUZARABANI = "Muzarabani";
    case RUSHINGA = "Rushinga";
    case SHAMVA = "Shamva";

    #MASHONALAND EAST
    case CHIKOMBA = "Chikomba";
    case GOROMONZI = "Goromonzi";
    case MARONDERA = "Marondera";
    case MUDZI = "Mudzi";
    case MUREHWA = "Murehwa";
    case MUTOKO = "Mutoko";
    case SEKE = "Seke";
    case UMP = "UMP (Uzumba-Maramba-Pfungwe)";
    case WEDZA = "Wedza (Hwedza)";

    # MASHONALAND WEST
    case CHEGUTU = "Chegutu";
    case HURUNGWE = "Hurungwe";
    case KARIBA = "Kariba";
    case MAKONDE = "Makonde";
    case MHONDORO_NGEZI = "Mhondoro-Ngezi";
    case SANYATI = "Sanyati";
    case ZVIMBA = "Zvimba";

    # MASVINGO
    case BIKITA = "Bikita";
    case CHIREDZI = "Chiredzi";
    case CHIVI = "Chivi";
    case GUTU = "Gutu";
    case MASVINGO = "Masvingo";
    case MWENEZI = "Mwenezi";
    case ZAKA = "Zaka";

    # MATEBELELAND NORTH
    case BINGA = "Binga";
    case BUBI = "Bubi";
    case HWANGE = "Hwange";
    case LUPANE = "Lupane";
    case NKAYI = "Nkayi";
    case TSHOLOTSHO = "Tsholotsho";
    case UMGUZA = "Umguza";

    # MATEBELELAND SOUTH
    case BEITBRIDGE = "Beitbridge";
    case BULILIMA = "Bulilima";
    case GWANDA = "Gwanda";
    case INSIZA = "Insiza";
    case MANGWE = "Mangwe";
    case MATOBO = "Matobo";
    case UMZINGWANE = "Umzingwane";

    # MIDLANDS
    case CHIRUMHANZU = "Chirumhanzu";
    case GOKWE_NORTH = "Gokwe North";
    case GOKWE_SOUTH = "Gokwe South";
    case GWERU = "Gweru";
    case KWEKWE = "Kwekwe";
    case MBERENGWA = "Mberengwa";
    case SHURUGWI = "Shurugwi";
    case ZVISHAVANE = "Zvishavane";


    public function label(): string
    {
        return match ($this) {
            # BULAWAYO
            self::BULAWAYO => 'Bulawayo',
            # HARARE
            self::HARARE => 'Harare',
            self::CHITUNGWIZA => "Chitungwiza",
            #MANICALAND
            self::BUHERA => "Buhera",
            self::CHIMANIMANI => "Chimanimani",
            self::CHIPINGE => "Chipinge",
            self::MAKONI => "Makoni",
            self::MUTARE => "Mutare",
            self::MUTASA => "Mutasa",
            self::NYANGA => "Nyanga",
            #MASHONALAND CENTRAL
            self::BINDURA => "Bindura",
            self::GURUVE => "Guruve",
            self::MAZOWE => "Mazowe",
            self::MBIRE => "Mbire",
            self::MOUNT_DARWIN => "Mount Darwin",
            self::MUZARABANI => "Muzarabani",
            self::RUSHINGA => "Rushinga",
            self::SHAMVA => "Shamva",
            #MASHONALAND EAST
            self::CHIKOMBA => "Chikomba",
            self::GOROMONZI => "Goromonzi",
            self::MARONDERA => "Marondera",
            self::MUDZI => "Mudzi",
            self::MUREHWA => "Murehwa",
            self::MUTOKO => "Mutoko",
            self::SEKE => "Seke",
            self::UMP => "UMP (Uzumba-Maramba-Pfungwe)",
            self::WEDZA => "Wedza (Hwedza)",
            # MASHONALAND WEST
            self::CHEGUTU => "Chegutu",
            self::HURUNGWE => "Hurungwe",
            self::KARIBA => "Kariba",
            self::MAKONDE => "Makonde",
            self::MHONDORO_NGEZI => "Mhondoro-Ngezi",
            self::SANYATI => "Sanyati",
            self::ZVIMBA => "Zvimba",
            # MASVINGO
            self::BIKITA => "Bikita",
            self::CHIREDZI => "Chiredzi",
            self::CHIVI => "Chivi",
            self::GUTU => "Gutu",
            self::MASVINGO => "Masvingo",
            self::MWENEZI => "Mwenezi",
            self::ZAKA => "Zaka",
            # MATEBELELAND NORTH
            self::BINGA => "Binga",
            self::BUBI => "Bubi",
            self::HWANGE => "Hwange",
            self::LUPANE => "Lupane",
            self::NKAYI => "Nkayi",
            self::TSHOLOTSHO => "Tsholotsho",
            self::UMGUZA => "Umguza",
            # MATEBELELAND SOUTH
            self::BEITBRIDGE => "Beitbridge",
            self::BULILIMA => "Bulilima",
            self::GWANDA => "Gwanda",
            self::INSIZA => "Insiza",
            self::MANGWE => "Mangwe",
            self::MATOBO => "Matobo",
            self::UMZINGWANE => "Umzingwane",
            # MIDLANDS
            self::CHIRUMHANZU => "Chirumhanzu",
            self::GOKWE_NORTH => "Gokwe North",
            self::GOKWE_SOUTH => "Gokwe South",
            self::GWERU => "Gweru",
            self::KWEKWE => "Kwekwe",
            self::MBERENGWA => "Mberengwa",
            self::SHURUGWI => "Shurugwi",
            self::ZVISHAVANE => "Zvishavane",
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

