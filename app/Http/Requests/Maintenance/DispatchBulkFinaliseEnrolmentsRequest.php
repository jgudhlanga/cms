<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Queries\Maintenance\VerifiedStudentsForFinalEnrolmentQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DispatchBulkFinaliseEnrolmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'student_application_ids' => ['sometimes', 'array'],
            'student_application_ids.*' => ['required', 'integer', 'exists:student_applications,id'],
            'force_finalise' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = $this->input('student_application_ids', []);

            if (! is_array($ids) || $ids === []) {
                return;
            }

            $verifiedIds = app(VerifiedStudentsForFinalEnrolmentQuery::class)
                ->baseQuery()
                ->whereIn('student_applications.id', $ids)
                ->pluck('student_applications.id')
                ->all();

            $invalidIds = array_values(array_diff(array_map('intval', $ids), array_map('intval', $verifiedIds)));

            if ($invalidIds !== []) {
                $validator->errors()->add(
                    'student_application_ids',
                    __('trans.maintenance_verified_students_final_enrolment_invalid_application_ids'),
                );
            }
        });
    }

    /**
     * @return list<int>
     */
    public function studentApplicationIds(): array
    {
        $ids = $this->input('student_application_ids', []);

        if (! is_array($ids) || $ids === []) {
            return [];
        }

        return array_values(array_map('intval', $ids));
    }

    public function forceFinalise(): bool
    {
        return $this->boolean('force_finalise')
            && $this->studentApplicationIds() !== [];
    }
}
