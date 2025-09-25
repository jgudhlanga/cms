<?php

namespace App\Repositories\Students;

use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Shared\NextOfKinDto;
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Students\StudentProgramDto;
use App\DTO\Students\UpdateStudentDto;
use App\Enums\Shared\AcademicLevelEnum;
use App\Http\Filters\Students\StudentFilter;
use App\Models\Shared\AcademicLevel;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use App\Repositories\Students\interface\IStudentRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StudentRepository extends BaseRepository implements IStudentRepository
{
    public function __construct(
        protected Student                   $student,
        protected IAddressRepository        $addressRepository,
        protected IContactRepository        $contactRepository,
        protected INextOfKinRepository      $nextOfKinRepository,
        protected IStudentProgramRepository $studentProgramRepository,
    )
    {
        parent::__construct($this->student);
    }

    public function create(CreateApplicationDto $dto): Model
    {
        $student = $this->student->create($this->createFields($dto))->refresh();
        $this->saveProgram($student, $dto);
        $this->saveContact($student, $dto);
        $this->saveAddress($student, $dto);
        $this->saveNextOfKin($student, $dto);
        $this->saveAcademicResults($student, $dto);
        return $student->refresh();
    }

    public function update(Student $student, UpdateStudentDto $dto)
    {
        return tap($student)->update($this->updateFields($dto));
    }

    public function allFilter($columns = ['*'], ?StudentFilter $filters = null)
    {
        return $this->student
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function createFields(CreateApplicationDto $dto): array
    {
        $cleanIdNumber = str_replace(' ', '', trim($dto->id_number));
        return [
            'user_id' => $dto->user_id,
            'title_id' => $dto->title_id,
            'gender_id' => $dto->gender_id,
            'marital_status_id' => $dto->marital_status_id,
            'race_id' => $dto->race_id,
            'id_type_id' => $dto->id_type_id,
            'id_number' => $cleanIdNumber,
            'passport_number' => $dto->passport_number,
            'country_id' => $dto->country_id,
            'study_permit_number' => $dto->study_permit_number,
            'required_exam_sitting_count' => $this->getRequiredExamSittingCount($dto->o_level_years, $dto->o_level_other_years),
            'date_of_birth' => Carbon::parse($dto->date_of_birth)->format('Y-m-d'),
        ];
    }

    private function updateFields(UpdateStudentDto $dto): array
    {
        return [
            'id_type_id' => $dto->id_type_id,
            'id_number' => $dto->id_number,
            'passport_number' => $dto->passport_number,
            'country_id' => $dto->country_id,
            'date_of_birth' => Carbon::parse($dto->date_of_birth)->format('Y-m-d'),
            'marital_status_id' => $dto->marital_status_id,
            'race_id' => $dto->race_id,
            'title_id' => $dto->title_id,
            'gender_id' => $dto->gender_id,
            'religion_id' => $dto->religion_id,
            'denomination' => $dto->denomination,
            'height' => $dto->height,
            'weight' => $dto->weight,
            'study_permit_number' => $dto->study_permit_number,
        ];
    }

    private function saveProgram(Student $student, CreateApplicationDto $dto): void
    {
        $programDto = new StudentProgramDto(
            student_id: $student->id,
            mode_of_study_id: $dto->mode_of_study_id,
            institution_department_id: $dto->department_id,
            department_level_id: $dto->level_id,
            department_course_id: $dto->course_id,
            intake_period_id: $dto->intake_period_id,
            required_level_completed: $dto->required_level_completed,
            read_write_acknowledged: $dto->read_write_acknowledged,
        );
        $this->studentProgramRepository->create($programDto);
    }

    private function saveContact(Student $student, CreateApplicationDto $dto): void
    {
        $nameParts = array_filter([
            $dto->first_name,
            $dto->middle_name,
            $dto->last_name,
        ]);
        $contactDto = new ContactDto(
            name: implode(' ', $nameParts),
            phone_number: $dto->phone_number,
            alt_phone_number: $dto->alt_phone_number,
            email_address: $dto->email,
            alt_email_address: null,
            contact_is_main: 1,
        );
        $this->contactRepository->create($student, $contactDto);
    }

    private function saveAddress(Student $student, CreateApplicationDto $dto): void
    {
        $addressDto = new AddressDto(
            address_1: $dto->address_1,
            address_2: $dto->address_2,
            address_3: $dto->address_3,
            address_4: $dto->address_4,
            address_5: null,
            address_6: null,
            address_is_main: 1,
        );
        $this->addressRepository->create($student, $addressDto);
    }

    private function saveNextOfKin(Student $student, CreateApplicationDto $dto): void
    {
        $nextOfKinDto = new NextOfKinDto(
            name: $dto->next_of_kin_name,
            relationship_id: $dto->relationship_id,
            phone_number: $dto->next_of_kin_phone_number,
            address_1: $dto->next_of_kin_address_1,
            address_2: $dto->next_of_kin_address_2,
            address_3: $dto->next_of_kin_address_3,
            address_4: $dto->next_of_kin_address_4,
        );
        $this->nextOfKinRepository->create($student, $nextOfKinDto);
    }

    private function saveAcademicResults(Student $student, CreateApplicationDto $dto): void
    {
        $mainSubjects = $dto->o_level_subject_ids;
        $examSittings = $dto->o_level_sittings;
        $examYears = $dto->o_level_years;
        $otherSubjects = $dto->o_level_other_subject_ids;
        $otherGrades = $dto->o_level_other_grade_ids;
        $otherExamYears = $dto->o_level_other_years;
        $otherSittings = $dto->o_level_other_sittings;
        $level = AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->first();
        if (!empty($mainSubjects) && is_array($mainSubjects)) {
            foreach ($mainSubjects as $subjectId => $gradeId) {
                $examSitting = $examSittings[$subjectId] ?? null;
                $examYear = $examYears[$subjectId] ?? null;
                $student->oLevelResults()->create([
                    'academic_level_id' => $level->id,
                    'subject_id' => $subjectId,
                    'exam_year' => $examYear,
                    'exam_sitting' => $examSitting['value'] ?? null,
                    'grade_id' => $gradeId,
                ]);
            }
        }
        if (!empty($otherSubjects) && is_array($otherSubjects)) {
            foreach ($otherSubjects as $key => $subject) {
                $otherGrade = $otherGrades[$key] ?? null;
                $otherSitting = $otherSittings[$key] ?? null;
                $otherExamYear = $otherExamYears[$key] ?? null;
                $student->oLevelResults()->create([
                    'academic_level_id' => $level->id,
                    'subject_id' => $subject['value'] ?? null,
                    'exam_year' => $otherExamYear,
                    'exam_sitting' => $otherSitting['value'] ?? null,
                    'grade_id' => $otherGrade,
                ]);
            }
        }
    }

    private function getRequiredExamSittingCount($examYears, $otherExamYears): int
    {
        $uniqueExamYears = array_values(
            array_unique(
                array_merge(
                    array_values($examYears ?? []),
                    array_values($otherExamYears ?? [])
                )
            )
        );
        return count($uniqueExamYears);
    }
}
