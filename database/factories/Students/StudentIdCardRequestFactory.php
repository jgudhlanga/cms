<?php

declare(strict_types=1);

namespace Database\Factories\Students;

use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Models\Students\StudentIdCardRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentIdCardRequest>
 */
class StudentIdCardRequestFactory extends Factory
{
    protected $model = StudentIdCardRequest::class;

    public function definition(): array
    {
        return [
            'status' => IdCardRequestStatusEnum::PENDING,
            'reason' => IdCardRequestReasonEnum::NEW,
            'notes' => null,
        ];
    }

    public function awaitingPayment(): static
    {
        return $this->state(fn (): array => [
            'status' => IdCardRequestStatusEnum::AWAITING_PAYMENT,
            'reason' => IdCardRequestReasonEnum::LOST,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => IdCardRequestStatusEnum::PENDING,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => IdCardRequestStatusEnum::APPROVED,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => IdCardRequestStatusEnum::REJECTED,
            'reviewed_at' => now(),
            'rejection_reason' => 'Photo does not meet passport requirements.',
        ]);
    }

    public function printed(): static
    {
        return $this->state(fn (): array => [
            'status' => IdCardRequestStatusEnum::PRINTED,
            'printed_at' => now(),
        ]);
    }

    public function issued(): static
    {
        return $this->state(fn (): array => [
            'status' => IdCardRequestStatusEnum::ISSUED,
            'issued_at' => now(),
        ]);
    }
}
