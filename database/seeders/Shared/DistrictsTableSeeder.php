<?php

namespace Database\Seeders\Shared;

use App\Enums\Shared\DistrictEnum;
use App\Enums\Shared\ProvinceEnum;
use App\Models\Shared\District;
use App\Models\Shared\Province;
use Illuminate\Database\Seeder;

class DistrictsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ProvinceEnum::BULAWAYO->value => [
                ['name' => DistrictEnum::BULAWAYO->value],
            ],
            ProvinceEnum::HARARE->value => [
                ['name' => DistrictEnum::CHITUNGWIZA->value],
                ['name' => DistrictEnum::HARARE->value],
            ],
            ProvinceEnum::MANICALAND->value => [
                ['name' => DistrictEnum::BUHERA->value],
                ['name' => DistrictEnum::CHIMANIMANI->value],
                ['name' => DistrictEnum::CHIPINGE->value],
                ['name' => DistrictEnum::MAKONI->value],
                ['name' => DistrictEnum::MUTARE->value],
                ['name' => DistrictEnum::MUTASA->value],
                ['name' => DistrictEnum::NYANGA->value],
            ],
            ProvinceEnum::MASHONALAND_CENTRAL->value => [
                ['name' => DistrictEnum::BINDURA->value],
                ['name' => DistrictEnum::GURUVE->value],
                ['name' => DistrictEnum::MAZOWE->value],
                ['name' => DistrictEnum::MBIRE->value],
                ['name' => DistrictEnum::MOUNT_DARWIN->value],
                ['name' => DistrictEnum::MUZARABANI->value],
                ['name' => DistrictEnum::RUSHINGA->value],
                ['name' => DistrictEnum::SHAMVA->value],
            ],
            ProvinceEnum::MASHONALAND_EAST->value => [
                ['name' => DistrictEnum::CHIKOMBA->value],
                ['name' => DistrictEnum::GOROMONZI->value],
                ['name' => DistrictEnum::MARONDERA->value],
                ['name' => DistrictEnum::MUDZI->value],
                ['name' => DistrictEnum::MUREHWA->value],
                ['name' => DistrictEnum::MUTOKO->value],
                ['name' => DistrictEnum::SEKE->value],
                ['name' => DistrictEnum::UMP->value],
                ['name' => DistrictEnum::WEDZA->value],
            ],
            ProvinceEnum::MASHONALAND_WEST->value => [
                ['name' => DistrictEnum::CHEGUTU->value],
                ['name' => DistrictEnum::HURUNGWE->value],
                ['name' => DistrictEnum::KARIBA->value],
                ['name' => DistrictEnum::MAKONDE->value],
                ['name' => DistrictEnum::MHONDORO_NGEZI->value],
                ['name' => DistrictEnum::SANYATI->value],
                ['name' => DistrictEnum::ZVIMBA->value],
            ],
            ProvinceEnum::MASVINGO->value => [
                ['name' => DistrictEnum::BIKITA->value],
                ['name' => DistrictEnum::CHIREDZI->value],
                ['name' => DistrictEnum::CHIVI->value],
                ['name' => DistrictEnum::GUTU->value],
                ['name' => DistrictEnum::MASVINGO->value],
                ['name' => DistrictEnum::MWENEZI->value],
                ['name' => DistrictEnum::ZAKA->value],
            ],
            ProvinceEnum::MATEBELELAND_NORTH->value => [
                ['name' => DistrictEnum::BINGA->value],
                ['name' => DistrictEnum::BUBI->value],
                ['name' => DistrictEnum::HWANGE->value],
                ['name' => DistrictEnum::LUPANE->value],
                ['name' => DistrictEnum::NKAYI->value],
                ['name' => DistrictEnum::TSHOLOTSHO->value],
                ['name' => DistrictEnum::UMGUZA->value],
            ],
            ProvinceEnum::MATEBELELAND_SOUTH->value => [
                ['name' => DistrictEnum::BEITBRIDGE->value],
                ['name' => DistrictEnum::BULILIMA->value],
                ['name' => DistrictEnum::GWANDA->value],
                ['name' => DistrictEnum::INSIZA->value],
                ['name' => DistrictEnum::MANGWE->value],
                ['name' => DistrictEnum::MATOBO->value],
                ['name' => DistrictEnum::UMZINGWANE->value],
            ],
            ProvinceEnum::MIDLANDS->value => [
                ['name' => DistrictEnum::CHIRUMHANZU->value],
                ['name' => DistrictEnum::GOKWE_NORTH->value],
                ['name' => DistrictEnum::GOKWE_SOUTH->value],
                ['name' => DistrictEnum::GWERU->value],
                ['name' => DistrictEnum::KWEKWE->value],
                ['name' => DistrictEnum::MBERENGWA->value],
                ['name' => DistrictEnum::SHURUGWI->value],
                ['name' => DistrictEnum::ZVISHAVANE->value],
            ],

        ];
        foreach ($data as $key => $rows) {
            $province = Province::where('title', $key)->first();
            foreach ($rows as $row) {
                $exist = District::where('name', $row['name'])->first();
                if (!$exist instanceof District) {
                    $row['province_id'] = $province?->id;
                    District::create($row);
                }
            }
        }
    }
}
