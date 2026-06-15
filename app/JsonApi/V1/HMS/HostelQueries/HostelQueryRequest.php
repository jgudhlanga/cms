<?php

namespace App\JsonApi\V1\HMS\HostelQueries;

use App\Enums\HMS\HostelQueryCategoryEnum;
use App\Enums\HMS\HostelQueryPriorityEnum;
use App\Enums\HMS\HostelQueryStatusEnum;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class HostelQueryRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'studentId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:students,id'],
            'category' => [$isCreating ? 'required' : 'sometimes', Rule::enum(HostelQueryCategoryEnum::class)],
            'subject' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => [$isCreating ? 'required' : 'sometimes', 'string'],
            'priority' => ['sometimes', Rule::enum(HostelQueryPriorityEnum::class)],
            'status' => ['sometimes', Rule::enum(HostelQueryStatusEnum::class)],
            'resolutionNotes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
