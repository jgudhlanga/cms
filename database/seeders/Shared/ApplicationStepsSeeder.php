<?php

namespace Database\Seeders\Shared;

use App\Enums\Institution\ApplicationStepEnum;
use App\Models\Shared\ApplicationStep;
use Illuminate\Database\Seeder;

class ApplicationStepsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => ApplicationStepEnum::DRAFT_INCOMPLETE->value, 'description' => 'Application started but not completed'],
            ['name' => ApplicationStepEnum::SUBMITTED->value, 'description' => 'Application has been submitted for review'],
            ['name' => ApplicationStepEnum::IN_REVIEW->value, 'description' => 'Application is currently being reviewed'],
            ['name' => ApplicationStepEnum::AWAITING_REQUIREMENTS->value, 'description' => 'Waiting for required documents or information'],
            ['name' => ApplicationStepEnum::AWAITING_PAYMENT->value, 'description' => 'Waiting for application fee payment'],
            ['name' => ApplicationStepEnum::INTERVIEW_SCHEDULED->value, 'description' => 'Interview has been scheduled'],
            ['name' => ApplicationStepEnum::INTERVIEW_COMPLETED->value, 'description' => 'Interview has been completed'],
            ['name' => ApplicationStepEnum::DECISION_PENDING->value, 'description' => 'Final decision is being prepared'],
            ['name' => ApplicationStepEnum::ACCEPTED_OFFER_MADE->value, 'description' => 'Offer of acceptance has been made'],
            ['name' => ApplicationStepEnum::WAITLISTED->value, 'description' => 'Application is on the waitlist'],
            ['name' => ApplicationStepEnum::REJECTED->value, 'description' => 'Application was not successful'],
            ['name' => ApplicationStepEnum::OFFER_ACCEPTED->value, 'description' => 'Offer has been accepted by the applicant'],
            ['name' => ApplicationStepEnum::OFFER_DECLINED->value, 'description' => 'Offer has been declined by the applicant'],
            ['name' => ApplicationStepEnum::ENROLLED_REGISTERED->value, 'description' => 'Applicant has enrolled and registered successfully'],
        ];
        foreach ($data as $row) {
            ApplicationStep::create($row);
        }
    }
}
