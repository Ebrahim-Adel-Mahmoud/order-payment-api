<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class BusinessRuleException extends UnprocessableEntityHttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
