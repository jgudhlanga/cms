<?php

namespace App\Observers\HMS;

use App\Enums\HMS\HostelNoticeStatusEnum;
use App\Models\HMS\HostelNotice;
use App\Services\HMS\HostelNoticeAudienceService;
use Carbon\Carbon;

class HostelNoticeObserver
{
    public function __construct(
        protected HostelNoticeAudienceService $audienceService,
    ) {}

    public function creating(HostelNotice $notice): void
    {
        if ($notice->posted_by === null && auth()->id() !== null) {
            $notice->posted_by = (int) auth()->id();
        }

        if ($notice->status === null) {
            $notice->status = HostelNoticeStatusEnum::PENDING;
        }

        $this->audienceService->applyPublishingRules($notice);
    }

    public function updating(HostelNotice $notice): void
    {
        if ($notice->isDirty('status')
            && $notice->status === HostelNoticeStatusEnum::PUBLISHED
            && $notice->published_at === null) {
            $notice->published_at = Carbon::now();
        }

        $this->audienceService->applyPublishingRules($notice);
    }

    public function saved(HostelNotice $notice): void
    {
        $hostelIds = data_get(request()->input('data'), 'attributes.audienceHostelIds');
        $floors = data_get(request()->input('data'), 'attributes.audienceFloors');
        $studentIds = data_get(request()->input('data'), 'attributes.audienceStudentIds');

        if (is_array($hostelIds) || is_array($floors) || is_array($studentIds)) {
            $this->audienceService->syncAudience(
                $notice,
                is_array($hostelIds) ? array_map('intval', $hostelIds) : [],
                is_array($floors) ? $floors : [],
                is_array($studentIds) ? array_map('intval', $studentIds) : [],
            );
        }
    }
}
