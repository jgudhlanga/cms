<?php

namespace App\JsonApi\V1\HMS\HostelNotices;

use App\Enums\HMS\HostelNoticeStatusEnum;
use App\Enums\HMS\HostelNoticeTypeEnum;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class HostelNoticeRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'title' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'content' => [$isCreating ? 'required' : 'sometimes', 'string'],
            'noticeType' => [$isCreating ? 'required' : 'sometimes', Rule::enum(HostelNoticeTypeEnum::class)],
            'status' => ['sometimes', Rule::enum(HostelNoticeStatusEnum::class)],
            'isUrgent' => ['sometimes', 'boolean'],
            'publishedAt' => ['sometimes', 'nullable', 'date'],
            'expiresAt' => ['sometimes', 'nullable', 'date'],
            'audienceHostelIds' => ['sometimes', 'array'],
            'audienceHostelIds.*' => ['integer', 'exists:hostels,id'],
            'audienceFloors' => ['sometimes', 'array'],
            'audienceFloors.*.hostelId' => ['required_with:audienceFloors', 'integer', 'exists:hostels,id'],
            'audienceFloors.*.floorNumber' => ['required_with:audienceFloors', 'integer', 'min:0'],
            'audienceStudentIds' => ['sometimes', 'array'],
            'audienceStudentIds.*' => ['integer', 'exists:students,id'],
        ];
    }
}
