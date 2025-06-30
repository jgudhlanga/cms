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
            ['name' => ApplicationStepEnum::DRAFT_INCOMPLETE->value, 'description' => 'Application started but not completed', 'position' => 1],
            ['name' => ApplicationStepEnum::SUBMITTED->value, 'description' => 'Application has been submitted for review', 'position' => 2],
            ['name' => ApplicationStepEnum::IN_REVIEW->value, 'description' => 'Application is currently being reviewed', 'position' => 3],
            ['name' => ApplicationStepEnum::AWAITING_REQUIREMENTS->value, 'description' => 'Waiting for required documents or information', 'position' => 4],
            ['name' => ApplicationStepEnum::AWAITING_PAYMENT->value, 'description' => 'Waiting for application fee payment', 'position' => 5],
            ['name' => ApplicationStepEnum::INTERVIEW_SCHEDULED->value, 'description' => 'Interview has been scheduled', 'position' => 6],
            ['name' => ApplicationStepEnum::INTERVIEW_COMPLETED->value, 'description' => 'Interview has been completed', 'position' => 7],
            ['name' => ApplicationStepEnum::DECISION_PENDING->value, 'description' => 'Final decision is being prepared', 'position' => 8],
            ['name' => ApplicationStepEnum::ACCEPTED_OFFER_MADE->value, 'description' => 'Offer of acceptance has been made', 'position' => 9],
            ['name' => ApplicationStepEnum::WAITLISTED->value, 'description' => 'Application is on the waitlist', 'position' => 10],
            ['name' => ApplicationStepEnum::REJECTED->value, 'description' => 'Application was not successful', 'position' => 11],
            ['name' => ApplicationStepEnum::OFFER_ACCEPTED->value, 'description' => 'Offer has been accepted by the applicant', 'position' => 12],
            ['name' => ApplicationStepEnum::OFFER_DECLINED->value, 'description' => 'Offer has been declined by the applicant', 'position' => 13],
            ['name' => ApplicationStepEnum::ENROLLED_REGISTERED->value, 'description' => 'Applicant has enrolled and registered successfully', 'position' => 14],
        ];
        foreach ($data as $row) {
            ApplicationStep::create($row);
        }
    }
}
