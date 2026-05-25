<?php

namespace App\Enums\Integrations\Banks;

enum ZBBankStatementFetchWindowStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
