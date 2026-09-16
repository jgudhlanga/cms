<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Assessment calendars with no reminder days set: the missing-marks reminders key off those days, so an
 * unset calendar quietly never reminds anyone.
 *
 * College-wide rather than departmental, so it carries no department and reaches the college-wide roles.
 */
class AssessmentCalendarDatesMissingCheck implements SetupGapCheck
{
    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::ASSESSMENT_CALENDAR_DATES_MISSING;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $rows = DB::table('assessment_calendars as ac')
            ->leftJoin('assessment_types as at', 'at.id', '=', 'ac.assessment_type_id')
            ->whereNull('ac.first_notification_days_before')
            ->whereNull('ac.second_notification_days_before')
            ->whereNull('ac.due_notification_days_before')
            ->select(['ac.id', 'ac.tenant_id', 'at.name as assessment_name'])
            ->get();

        $gaps = [];

        foreach ($rows as $row) {
            $assessmentName = (string) ($row->assessment_name ?? __('trans.assessment_type'));

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: (int) $row->tenant_id,
                title: __('setup_gaps.assessment_calendar_dates_missing_title', ['assessment' => $assessmentName]),
                body: __('setup_gaps.assessment_calendar_dates_missing_body'),
                url: route('academic-calendars.index'),
                meta: ['assessmentCalendarId' => (int) $row->id],
                fingerprintKey: "assessment-calendar:{$row->id}",
            );
        }

        return $gaps;
    }
}
