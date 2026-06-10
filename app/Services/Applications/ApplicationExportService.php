<?php

declare(strict_types=1);

namespace App\Services\Applications;

use App\Mail\Applications\ApplicationExportMail;
use App\Models\Students\StudentProgram;
use App\Queries\Applications\ApplicationExportQuery;
use App\Services\Students\CourseSyllabusCodeResolver;
use App\Support\Exports\CsvExportWriter;
use App\Support\Exports\StudentExportRowMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class ApplicationExportService
{
    public const OUTPUT_PATH = 'reports/applications/Application.csv';

    /**
     * @var list<string>
     */
    public const HEADERS = [
        'National_ID',
        'Is_International (Yes/No)',
        'First_Names',
        'Surname',
        'Gender',
        'Date_of_Birth (DD/MM/YYYY)',
        'Resident_Country',
        'Citizenship_Country',
        'Phone',
        'Email',
        'Resident_City',
        'Programme_Code',
        'Choice',
        'Accepted (Yes/No)',
        'Attendance_Type',
        'Entry_Type',
    ];

    public function __construct(
        protected ApplicationExportQuery $query,
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
                    ->chunkById(200, function (Collection $programs) use ($handle): void {
                        foreach ($programs as $program) {
                            /** @var StudentProgram $program */
                            fputcsv($handle, $this->mapRow($program));
                        }
                    }, 'student_programs.id', 'id');
            },
        );

        logger()->info('Application CSV export completed.', [
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
            logger()->warning('Application export: no recipient emails provided.');

            return;
        }

        Mail::to($recipientEmails)->send(new ApplicationExportMail(
            reportPath: $relativePath,
            intakeYear: $intakeYear,
        ));
    }

    /**
     * @return list<string|null>
     */
    public function mapRow(StudentProgram $program): array
    {
        $student = $program->student;
        $user = $student?->user;
        $mainAddress = $this->studentExportRowMapper->resolveMainAddress($student);
        $countryName = $student?->country?->name;
        $modeOfStudyName = $program->modeOfStudy?->name;

        return [
            $this->studentExportRowMapper->resolveNationalId($student),
            $this->studentExportRowMapper->resolveInternationalFlag($student),
            $this->studentExportRowMapper->resolveFirstNames($user?->first_name, $user?->middle_name),
            $user?->last_name,
            $student?->gender?->title,
            $this->studentExportRowMapper->resolveDateOfBirth($student),
            $countryName,
            $countryName,
            $this->studentExportRowMapper->resolvePhone($student),
            $this->studentExportRowMapper->resolveEmail($user, $student),
            $mainAddress?->address_3,
            $this->courseSyllabusCodeResolver->resolveForProgram($program),
            '1',
            'Yes',
            $modeOfStudyName,
            $modeOfStudyName,
        ];
    }
}
