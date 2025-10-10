<?php

namespace App\Helpers;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\StatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Subject;
use App\Models\Shared\Status;
use App\Models\Students\Student;
use App\Models\Students\StudentAcademicResult;
use App\Models\Tenants\Tenant;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class StudentHelper
{
    public static function getOLevelSubjectsLeftJoinedToStudentResults(Student $student): Collection
    {
        return Subject::leftJoin('student_academic_results', function ($join) use ($student) {
            $join->on('subjects.id', '=', 'student_academic_results.subject_id')
                ->where('student_academic_results.student_id', $student->id)
                ->where('student_academic_results.academic_level_id', AcademicLevelEnum::SECONDARY_SCHOOL->id());
        })->leftJoin('grades', 'grades.id', '=', 'student_academic_results.grade_id')
            ->select('subjects.id as subject_id',
                'subjects.name as subject',
                'student_academic_results.exam_year',
                'student_academic_results.student_id',
                'student_academic_results.exam_sitting',
                'student_academic_results.grade_id',
                'grades.name as grade',
                'student_academic_results.id as result_id')
            ->get();
    }

    public static function getStudentOLevelResultsJoinedToSubjects(Student $student): Collection
    {
        return StudentAcademicResult::join('subjects', function ($join) use ($student) {
            $join->on('subjects.id', '=', 'student_academic_results.subject_id')
                ->where('student_academic_results.student_id', $student->id)
                ->where('student_academic_results.academic_level_id', AcademicLevelEnum::SECONDARY_SCHOOL->id());
        })->leftJoin('grades', 'grades.id', '=', 'student_academic_results.grade_id')
            ->select('subjects.id as subject_id',
                'subjects.name as subject',
                'student_academic_results.exam_year',
                'student_academic_results.exam_sitting',
                'student_academic_results.grade_id',
                'student_academic_results.student_id',
                'grades.name as grade',
                'student_academic_results.id as result_id')
            ->get();
    }
}
