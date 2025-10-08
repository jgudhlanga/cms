<?php

namespace App\Enums\Shared;

enum PaymentModeEnum: string
{
    case Online = 'online';
    case Cash = 'cash';
}
