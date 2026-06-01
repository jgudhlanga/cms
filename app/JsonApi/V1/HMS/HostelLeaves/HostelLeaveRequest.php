<?php

namespace App\JsonApi\V1\HMS\HostelLeaves;

use App\Enums\HMS\HostelLeaveStatusEnum;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class HostelLeaveRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'studentId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:students,id'],
            'leaveType' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:100'],
            'fromDate' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'toDate' => [$isCreating ? 'required' : 'sometimes', 'date', 'after_or_equal:fromDate'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(HostelLeaveStatusEnum::class)],
            'reviewNotes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
