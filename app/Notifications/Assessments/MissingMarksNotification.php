<?php

namespace App\Notifications\Assessments;

use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MissingMarksNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<array<string, mixed>>  $groupedRows
     */
    public function __construct(
        public AssessmentCalendar $calendar,
        public MissingMarksNotificationTierEnum $tier,
        public int $incompleteCount,
        public array $groupedRows,
        public bool $isEscalation = false,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $assessmentName = (string) ($this->calendar->assessmentType?->name ?? __('trans.assessment_type'));
        $endDate = $this->calendar->end_date?->toDateString() ?? '';
        $name = $notifiable->full_name ?? $notifiable->email;

        $mail = (new MailMessage)
            ->subject($this->subjectLine($assessmentName))
            ->greeting("Hello {$name},")
            ->line($this->introLine($assessmentName, $endDate))
            ->line(__('assessments.missing_marks_mail_count', ['count' => $this->incompleteCount]));

        foreach (array_slice($this->groupedRows, 0, 8) as $row) {
            $lecturers = implode(', ', $row['lecturerNames'] ?? []) ?: '—';
            $mail->line(sprintf(
                '%s / %s — %s (%s)',
                $row['className'] ?? '',
                $row['moduleName'] ?? '',
                trans_choice('assessments.missing_marks_incomplete_count', (int) ($row['incompleteCount'] ?? 0), [
                    'count' => (int) ($row['incompleteCount'] ?? 0),
                ]),
                $lecturers,
            ));
        }

        if (count($this->groupedRows) > 8) {
            $mail->line(__('assessments.missing_marks_mail_more', [
                'count' => count($this->groupedRows) - 8,
            ]));
        }

        if ($this->isEscalation) {
            $mail->line(__('assessments.missing_marks_mail_escalated'));
        }

        return $mail->line(__('assessments.missing_marks_mail_footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->isEscalation ? 'missing_marks_escalation' : 'missing_marks',
            'tier' => $this->tier->value,
            'assessmentCalendarId' => $this->calendar->id,
            'assessmentTypeName' => $this->calendar->assessmentType?->name,
            'endDate' => $this->calendar->end_date?->toDateString(),
            'incompleteCount' => $this->incompleteCount,
        ];
    }

    private function subjectLine(string $assessmentName): string
    {
        if ($this->isEscalation) {
            return __('assessments.missing_marks_mail_subject_escalation', ['assessment' => $assessmentName]);
        }

        return match ($this->tier) {
            MissingMarksNotificationTierEnum::First => __('assessments.missing_marks_mail_subject_first', [
                'assessment' => $assessmentName,
            ]),
            MissingMarksNotificationTierEnum::Second => __('assessments.missing_marks_mail_subject_second', [
                'assessment' => $assessmentName,
            ]),
            MissingMarksNotificationTierEnum::Due => __('assessments.missing_marks_mail_subject_due', [
                'assessment' => $assessmentName,
            ]),
        };
    }

    private function introLine(string $assessmentName, string $endDate): string
    {
        if ($this->isEscalation) {
            return __('assessments.missing_marks_mail_intro_escalation', [
                'assessment' => $assessmentName,
                'end_date' => $endDate,
            ]);
        }

        return match ($this->tier) {
            MissingMarksNotificationTierEnum::First => __('assessments.missing_marks_mail_intro_first', [
                'assessment' => $assessmentName,
                'end_date' => $endDate,
            ]),
            MissingMarksNotificationTierEnum::Second => __('assessments.missing_marks_mail_intro_second', [
                'assessment' => $assessmentName,
                'end_date' => $endDate,
            ]),
            MissingMarksNotificationTierEnum::Due => __('assessments.missing_marks_mail_intro_due', [
                'assessment' => $assessmentName,
                'end_date' => $endDate,
            ]),
        };
    }
}
