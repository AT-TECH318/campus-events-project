<?php

namespace App\Exceptions;

use RuntimeException;

class EventRegistrationException extends RuntimeException
{
    public const ALREADY_REGISTERED = 'already_registered';

    public const AT_CAPACITY = 'at_capacity';

    public const DEADLINE_PASSED = 'deadline_passed';

    public const NOT_OPEN = 'not_open';

    public const NOT_REGISTERED = 'not_registered';

    public function __construct(public string $reason, string $message)
    {
        parent::__construct($message);
    }
}
