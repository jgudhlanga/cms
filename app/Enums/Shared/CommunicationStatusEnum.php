<?php

namespace App\Enums\Shared;

enum CommunicationStatusEnum: string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case PENDING = 'pending';
    case OPENED = 'opened';
    case READ = 'read';
    case VIEWED = 'viewed';
}
