<?php

namespace App\JsonApi\V1\HMS\HostelApplications;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\JsonApi\V1\HMS\HostelApplications\Filters\ApplicationSearchFilter;
use App\JsonApi\V1\HMS\HostelApplications\Filters\ApplicationStatusFilter;
use App\JsonApi\V1\HMS\HostelApplications\Filters\ApplicationStudentFilter;
use App\JsonApi\V1\HMS\HostelApplications\Filters\ApplicationTypeFilter;
use App\Models\HMS\HostelApplication;
use App\Services\HMS\StudentPhysicalAddressFormatter;
use App\Support\HMS\HostelApplicationPaymentVerification;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\QueryBuilder\JsonApiBuilder;
use LaravelJsonApi\Eloquent\Schema;

class HostelApplicationSchema extends Schema
{
    public static string $model = HostelApplication::class;

    protected ?string $uriType = 'hms/hostel-applications';

    protected array $with = [
        'student.user',
        'student.gender',
        'student.addresses',
        'student.latestEnrolment.institutionDepartment.department',
        'student.latestEnrolment.departmentLevel.level',
        'student.latestEnrolment.departmentCourse.course',
        'student.latestEnrolment.studentProgram.intakePeriod',
        'studentEnrolment.institutionDepartment.department',
        'studentEnrolment.departmentCourse.course',
        'studentEnrolment.departmentLevel.level',
        'studentEnrolment.studentProgram.intakePeriod',
        'gender',
    ];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('applicationType', 'type')->extractUsing(
                fn (HostelApplication $application) => $application->type?->value
            ),
            Str::make('applicationTypeLabel')->extractUsing(
                fn (HostelApplication $application) => $application->type?->label()
            )->readOnly(),
            Str::make('status', 'status')->extractUsing(
                fn (HostelApplication $application) => $application->status?->value
            )->sortable(),
            Str::make('statusLabel')->extractUsing(
                fn (HostelApplication $application) => $application->status?->label()
            )->readOnly(),
            Number::make('studentId', 'student_id'),
            Number::make('studentEnrolmentId', 'student_enrolment_id'),
            Str::make('name'),
            Number::make('genderId', 'gender_id'),
            Str::make('phoneNumber', 'phone_number'),
            Str::make('emailAddress', 'email_address'),
            Str::make('nextOfKinName', 'next_of_kin_name'),
            Str::make('nextOfKinContact', 'next_of_kin_contact'),
            DateTime::make('checkIn', 'check_in'),
            DateTime::make('checkOut', 'check_out'),
            ArrayList::make('eligibilityResults', 'eligibility_results')->readOnly(),
            ArrayHash::make('paymentVerification', 'payment_verification')
                ->camelizeFields()
                ->snakeKeys()
                ->fillUsing(function (HostelApplication $application, string $column, $value): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $application->payment_verification = array_merge(
                        HostelApplicationPaymentVerification::normalize($application->payment_verification),
                        $value,
                    );
                }),
            Str::make('declineReason', 'decline_reason'),
            Number::make('hostelRoomId')->deserializeUsing(static fn () => null),
            Str::make('studentNumber')->extractUsing(
                fn (HostelApplication $application) => $application->student?->student_number
            )->readOnly(),
            Str::make('studentName')->extractUsing(
                fn (HostelApplication $application) => $application->type?->value === 'guest'
                    ? $application->name
                    : $application->student?->user?->full_name
            )->readOnly(),
            Str::make('displayName')->extractUsing(
                fn (HostelApplication $application) => $application->type?->value === 'guest'
                    ? $application->name
                    : ($application->student?->user?->full_name ?? $application->name)
            )->readOnly(),
            Str::make('gender')->extractUsing(
                fn (HostelApplication $application) => $application->gender?->title
                    ?? $application->student?->gender?->title
            )->readOnly(),
            Str::make('course')->extractUsing(
                fn (HostelApplication $application) => $application->studentEnrolment?->departmentCourse?->course?->name
                    ?? $application->student?->latestEnrolment?->departmentCourse?->course?->name
            )->readOnly(),
            Str::make('level')->extractUsing(
                fn (HostelApplication $application) => $application->studentEnrolment?->departmentLevel?->level?->name
                    ?? $application->student?->latestEnrolment?->departmentLevel?->level?->name
            )->readOnly(),
            Str::make('departmentName')->extractUsing(
                fn (HostelApplication $application) => $application->studentEnrolment?->institutionDepartment?->department?->name
                    ?? $application->student?->latestEnrolment?->institutionDepartment?->department?->name
            )->readOnly(),
            Str::make('calendarYear')->extractUsing(
                fn (HostelApplication $application) => $application->studentEnrolment?->studentProgram?->intakePeriod?->calendar_year
                    ?? $application->student?->latestEnrolment?->studentProgram?->intakePeriod?->calendar_year
            )->readOnly(),
            Str::make('physicalAddress')->extractUsing(
                fn (HostelApplication $application) => StudentPhysicalAddressFormatter::fromStudent($application->student)
            )->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            new ApplicationSearchFilter,
            new ApplicationTypeFilter,
            new ApplicationStatusFilter,
            new ApplicationStudentFilter,
            TrashedFilter::make(),
        ];
    }

    public function newQuery($query = null): JsonApiBuilder
    {
        $builder = parent::newQuery($query);
        $eloquent = $builder->getQuery();

        if (request()->query('sort') === null) {
            $eloquent->orderByRaw(
                'CASE hostel_applications.status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 WHEN ? THEN 4 WHEN ? THEN 5 ELSE 6 END',
                [
                    HostelApplicationStatusEnum::PENDING->value,
                    HostelApplicationStatusEnum::AWAITING_PAYMENT->value,
                    HostelApplicationStatusEnum::PARTIALLY_PAID->value,
                    HostelApplicationStatusEnum::PAID->value,
                    HostelApplicationStatusEnum::APPROVED->value,
                    HostelApplicationStatusEnum::DECLINED->value,
                ]
            )
                ->orderByDesc('hostel_applications.address_outside_campus_priority')
                ->latest('hostel_applications.created_at');
        }

        return $builder;
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }
}
