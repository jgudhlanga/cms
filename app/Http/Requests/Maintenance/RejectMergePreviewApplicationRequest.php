<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Models\Students\StudentApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RejectMergePreviewApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'target_student_id' => ['required', 'integer', Rule::exists('students', 'id'), 'different:source_student_id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $studentApplication = $this->route('student_application');

            if (! $studentApplication instanceof StudentApplication) {
                return;
            }

            $participantIds = [
                (int) $this->input('source_student_id'),
                (int) $this->input('target_student_id'),
            ];

            if (! in_array((int) $studentApplication->student_id, $participantIds, true)) {
                $validator->errors()->add(
                    'student_application',
                    __('trans.maintenance_faulty_data_merge_reject_not_participant'),
                );
            }
        });
    }
}
