<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class UnsupportedPaymentMethodException extends UnprocessableEntityHttpException
{
    public function __construct(string $method)
    {
        parent::__construct(sprintf('Payment method "%s" is not supported.', $method));
    }
}
