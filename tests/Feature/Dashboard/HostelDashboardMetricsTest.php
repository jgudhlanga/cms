<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\HMS\HostelQueryCategoryEnum;
use App\Enums\HMS\HostelQueryPriorityEnum;
use App\Enums\HMS\HostelQueryStatusEnum;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelQuery;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Users\User;

beforeEach(function () {
    enableDashboardModule();
});

test('dashboard returns hostel metrics for users with hostel tab access', function () {
    $user = userWithHostelDashboardPermission();
    $this->actingAs($user);
    $tenantId = $user->tenant_id;

    $maleGender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $femaleGender = Gender::query()->firstOrCreate(['title' => 'Female']);

    $maleRoom = createHostelRoomForAllocationIndexTest([
        'hostel' => [
            'tenant_id' => $tenantId,
            'name' => 'Block A',
            'capacity' => 10,
            'type' => 'male',
        ],
        'room' => [
            'tenant_id' => $tenantId,
            'current_occupancy' => 2,
            'max_occupancy' => 2,
            'status' => 'occupied',
        ],
    ]);

    $maintenanceRoom = createHostelRoomForAllocationIndexTest([
        'hostel' => [
            'tenant_id' => $tenantId,
            'name' => 'Block B',
            'capacity' => 8,
            'type' => 'female',
        ],
        'room' => [
            'tenant_id' => $tenantId,
            'status' => 'maintenance',
        ],
    ]);

    HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $maintenanceRoom->hostel_id,
        'name' => 'B-02',
        'room_type' => 'double',
        'capacity' => 4,
        'max_occupancy' => 4,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    $maleStudent = createStudentForDashboardHostelTest($maleGender, $tenantId);
    $maleStudent->update(['gender_id' => $maleGender->id]);

    $femaleStudent = createStudentForDashboardHostelTest($femaleGender, $tenantId);

    HostelRoomAllocation::query()->create([
        'tenant_id' => $tenantId,
        'hostel_room_id' => $maleRoom->id,
        'student_id' => $maleStudent->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
    ]);

    HostelRoomAllocation::query()->create([
        'tenant_id' => $tenantId,
        'hostel_room_id' => $maintenanceRoom->id,
        'student_id' => $femaleStudent->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
    ]);

    HostelQuery::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $maleStudent->id,
        'category' => HostelQueryCategoryEnum::MAINTENANCE->value,
        'subject' => 'Leaking pipe',
        'description' => 'Pipe burst in corridor',
        'priority' => HostelQueryPriorityEnum::HIGH->value,
        'status' => HostelQueryStatusEnum::OPEN->value,
    ]);

    HostelQuery::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $femaleStudent->id,
        'category' => HostelQueryCategoryEnum::MAINTENANCE->value,
        'subject' => 'Resolved issue',
        'description' => 'Fixed light',
        'priority' => HostelQueryPriorityEnum::LOW->value,
        'status' => HostelQueryStatusEnum::RESOLVED->value,
        'updated_at' => now(),
    ]);

    createHostelApplicationForDashboardTest($maleStudent, HostelApplicationStatusEnum::PAID, $tenantId);
    createHostelApplicationForDashboardTest($femaleStudent, HostelApplicationStatusEnum::AWAITING_PAYMENT, $tenantId);
    createHostelApplicationForDashboardTest(createStudentForDashboardHostelTest($maleGender, $tenantId), HostelApplicationStatusEnum::DECLINED, $tenantId);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->has('hostelDashboard')
            ->where('hostelDashboard.summary.blocks', 2)
            ->where('hostelDashboard.summary.totalCapacity', 18)
            ->where('hostelDashboard.summary.occupiedBeds', 2)
            ->where('hostelDashboard.summary.availableBeds', 16)
            ->where('hostelDashboard.summary.vacantRooms', 1)
            ->where('hostelDashboard.genderSplit.male', 1)
            ->where('hostelDashboard.genderSplit.female', 1)
            ->where('hostelDashboard.queryStats.open', 1)
            ->where('hostelDashboard.queryStats.highPriority', 1)
            ->where('hostelDashboard.queryStats.resolvedThisMonth', 1)
            ->where('hostelDashboard.applicationStats.total', 3)
            ->where('hostelDashboard.applicationStats.paid', 1)
            ->where('hostelDashboard.applicationStats.awaitingPayment', 1)
            ->where('hostelDashboard.applicationStats.declined', 1)
            ->where('hostelDashboard.applicationStats.paidRate', 33)
        );
});

test('dashboard omits hostel metrics when hostel tab is not visible', function () {
    $user = userWithDashboardPermission('view:dashboards');

    enableDashboardModule([
        'hostel' => false,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('hostelDashboard', null)
        );
});

function createStudentForDashboardHostelTest(Gender $gender, int $tenantId): Student
{
    $title = Title::query()->firstOrCreate(['name' => 'Ms Dash']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Dash']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Dash']);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Dash',
        'last_name' => 'Female',
    ]);

    return Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => '63-'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT).'DF',
        'date_of_birth' => '2002-06-01',
    ]);
}

function createHostelApplicationForDashboardTest(Student $student, HostelApplicationStatusEnum $status, int $tenantId): HostelApplication
{
    return HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => $status,
        'next_of_kin_name' => 'Kin',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));
}
