<?php

namespace Database\Seeders\Institution;

use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\DepartmentEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Race;
use App\Models\Shared\Title;
use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffSeeder extends Seeder
{
    private function getTenantId(): int
    {
        return TenantEnum::HARARE_POLY->id();
    }

    public function run(): void
    {
        DB::transaction(function () {
            $roles = [RoleEnum::LECTURER, RoleEnum::SENIOR_LECTURER];

            $users = User::factory()->count(10)->create(['tenant_id' => $this->getTenantId(), 'password' => 'Staff123!']);
            // Shuffle users to make assignment random
            $shuffledUsers = $users->shuffle();

            // Assign HEAD_OF_DEPARTMENT to the first user
            $headUser = $shuffledUsers->shift(); // Remove and get the first user
            $headUser->assignRole(RoleEnum::HEAD_OF_DEPARTMENT->name());
            $this->createStaff($headUser);
            // LIC
            $lic = $shuffledUsers->shift();
            $lic->assignRole(RoleEnum::LECTURER_IN_CHARGE->name());
            $this->createStaff($lic);
            // SELECTION_OFFICER
            $selectionOfficer = $shuffledUsers->shift();
            $selectionOfficer->assignRole(RoleEnum::SELECTION_OFFICER->name());
            $this->createStaff($selectionOfficer);
            foreach ($shuffledUsers as $user) {
                $randomRole = $roles[array_rand($roles)];
                $user->assignRole($randomRole->name());
                $this->createStaff($user);
            }
        });
    }


    private function createStaff(User $user): void
    {
        # date of birth range for staff
        $dateOfBirthStart = Carbon::now()->subYears(70);
        $dateOfBirthEnd = Carbon::now()->subYears(16);
        $genderIds = Gender::all()->pluck('id')->toArray();
        $titleIds = Title::all()->pluck('id')->toArray();
        $maritalStatusIds = MaritalStatus::all()->pluck('id')->toArray();
        $employmentTypeIds = EmploymentType::all()->pluck('id')->toArray();
        $raceIds = Race::all()->pluck('id')->toArray();
        $staff = Staff::create([
            'user_id' => $user->id,
            'tenant_id' => $this->getTenantId(),
            'employee_number' => fake()->unique()->numerify('EC-#####'),
            'date_of_birth' => Carbon::createFromTimestamp(rand($dateOfBirthStart->timestamp, $dateOfBirthEnd->timestamp))->format('Y-m-d'),
            'marital_status_id' => fake()->randomElement($maritalStatusIds),
            'race_id' => fake()->randomElement($raceIds),
            'title_id' => fake()->randomElement($titleIds),
            'gender_id' => fake()->randomElement($genderIds),
            'employment_type_id' => fake()->randomElement($employmentTypeIds),
        ]);

        $staff->institutionDepartments()->syncWithoutDetaching([$this->getInstitutionDepartmentId()]);
    }

    private function getInstitutionDepartmentId(): int
    {
        $itDepartment = DepartmentEnum::INFORMATION_COMMUNICATION_TECHNOLOGY->value;
        $itDepartmentId = Department::where('name', $itDepartment)->value('id');
        return InstitutionDepartment::where('department_id', $itDepartmentId)
            ->where('tenant_id', $this->getTenantId())
            ->value('id');
    }
}
