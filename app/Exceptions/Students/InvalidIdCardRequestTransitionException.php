<?php

declare(strict_types=1);

namespace App\Exceptions\Students;

use App\Enums\Students\IdCardRequestStatusEnum;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidIdCardRequestTransitionException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(422, $message);
    }

    public static function cannotTransition(
        IdCardRequestStatusEnum $current,
        IdCardRequestStatusEnum $target,
    ): self {
        return new self(__('students.id_card_invalid_transition', [
            'current' => $current->label(),
            'target' => $target->label(),
        ]));
    }

    public static function because(string $translationKey, array $replace = []): self
    {
        return new self(__($translationKey, $replace));
    }
}
