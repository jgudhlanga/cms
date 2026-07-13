<?php

declare(strict_types=1);

namespace App\Exceptions\AccountPurge;

use Exception;

class AccountPurgeArchiveRestoreException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct($message);
    }
}
