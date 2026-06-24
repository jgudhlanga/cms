<?php

declare(strict_types=1);

namespace App\Services\Enrolments;

use App\Mail\Enrolments\StudentEnrollmentExportMail;
use App\Models\Shared\NextOfKin;
use App\Models\Students\StudentEnrolment;
use App\Queries\Enrolments\StudentEnrollmentExportQuery;
use App\Services\Students\CourseSyllabusCodeResolver;
use App\Support\Exports\CsvExportWriter;
use App\Support\Exports\StudentExportRowMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class StudentEnrollmentExportService
{
    public const OUTPUT_PATH = 'reports/enrolments/Student_Enrollment.csv';

    /**
     * @var list<string>
     */
    public const HEADERS = [
        'Reg_Number',
        'First_Names',
        'Surname',
        'National_ID',
        'Gender',
        'Date_of_Birth (DD/MM/YYYY)',
        'Address_Town',
        'Address_Country',
        'Street_Name',
        'Street_Number',
        'Programme_Code',
        'Intake_Year',
        'Attendance_Type',
        'Study_Level_Year',
        'Study_Level_Semester',
        'Is_International (Yes/No)',
        'IsSponsored(Yes/No)',
        'SponsorName',
        "NextOfKinType('Parent','Guardian','Sibling','Spouse','Relative','Other')",
        'Relationship',
        'Fullname',
        'Email',
        'Phones',
        'Address',
        'Occupation',
        'Employer',
        'IsEmergency',
        'IsFinancialResponsible',
    ];

    public function __construct(
        protected StudentEnrollmentExportQuery $query,
        protected CourseSyllabusCodeResolver $courseSyllabusCodeResolver,
        protected CsvExportWriter $csvExportWriter,
        protected StudentExportRowMapper $studentExportRowMapper,
    ) {}

    public function export(?string $intakeYear = null, array $recipientEmails = []): string
    {
        $relativePath = $this->csvExportWriter->write(
            self::OUTPUT_PATH,
            self::HEADERS,
            function ($handle) use ($intakeYear): void {
                $this->query
                    ->baseQuery($intakeYear)
                    ->chunkById(200, function (Collection $enrolments) use ($handle): void {
                        foreach ($enrolments as $enrolment) {
                            /** @var StudentEnrolment $enrolment */
                            fputcsv($handle, $this->mapRow($enrolment));
                        }
                    }, column: 'id');
            },
        );

        logger()->info('Student enrollment CSV export completed.', [
            'path' => $relativePath,
            'intake_year' => $intakeYear,
            'recipient_emails' => $recipientEmails,
        ]);

        $this->sendCompletionEmail($relativePath, $intakeYear, $recipientEmails);

        return $relativePath;
    }

    /**
     * @param  list<string>  $recipientEmails
     */
    private function sendCompletionEmail(string $relativePath, ?string $intakeYear, array $recipientEmails): void
    {
        $recipientEmails = array_values(array_unique(array_filter($recipientEmails)));

        if ($recipientEmails === []) {
            logger()->warning('Student enrollment export: no recipient emails provided.');

            return;
        }

        Mail::to($recipientEmails)->send(new StudentEnrollmentExportMail(
            reportPath: $relativePath,
            intakeYear: $intakeYear,
        ));
    }

    /**
     * @return list<string|null>
     */
    public function mapRow(StudentEnrolment $enrolment): array
    {
        $student = $enrolment->student;
        $user = $student?->user;
        $studentApplication = $enrolment->studentApplication;
        $mainAddress = $this->studentExportRowMapper->resolveMainAddress($student);
        $nextOfKin = $student?->nextOfKins->first();
        $sponsor = $student?->sponsors->first();
        $relationshipName = $nextOfKin?->relationship?->name;

        return [
            $student?->student_number,
            $this->studentExportRowMapper->resolveFirstNames($user?->first_name, $user?->middle_name),
            $user?->last_name,
            $this->studentExportRowMapper->resolveNationalId($student),
            $student?->gender?->title,
            $this->studentExportRowMapper->resolveDateOfBirth($student),
            $mainAddress?->address_3,
            null,
            $mainAddress?->address_2,
            $mainAddress?->address_1,
            $this->courseSyllabusCodeResolver->resolve($enrolment),
            $studentApplication?->intakePeriod?->calendar_year,
            $studentApplication?->modeOfStudy?->name,
            $enrolment->academicCalendar?->calendar_year,
            $enrolment->academicYearOption?->name,
            $this->studentExportRowMapper->resolveInternationalFlag($student),
            $sponsor !== null ? 'Yes' : 'No',
            $sponsor?->name,
            $relationshipName,
            $relationshipName,
            $nextOfKin?->name,
            $nextOfKin?->contacts->first()?->email_address,
            $nextOfKin?->contacts->first()?->phone_number,
            $this->formatAddress($nextOfKin),
            null,
            null,
            null,
            null,
        ];
    }

    private function formatAddress(?NextOfKin $nextOfKin): ?string
    {
        if ($nextOfKin === null) {
            return null;
        }

        $address = $nextOfKin->addresses->first();

        if ($address === null) {
            return null;
        }

        $formatted = trim(implode(', ', array_filter([
            $address->address_1,
            $address->address_2,
            $address->address_3,
            $address->address_4,
        ])));

        return $formatted !== '' ? $formatted : null;
    }
}
