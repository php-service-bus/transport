<?php

/**
 * Common transport implementation interfaces.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Common\Exceptions;

/**
 * Incorrect connection settings for transport.
 */
final class InvalidConnectionParameters extends \InvalidArgumentException implements TransportFail
{
    /**
     * @codeCoverageIgnore
     */
    public static function emptyDSN(): self
    {
        return new self('Connection DSN can\'t be empty');
    }

    /**
     * @codeCoverageIgnore
     */
    public static function incorrectDSN(string $dsn): self
    {
        return new self(\sprintf('Can\'t parse specified connection DSN (%s)', $dsn));
    }
}
