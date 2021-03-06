<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Redis\Exceptions;

/**
 *
 */
final class IncorrectConnectionParameters extends \InvalidArgumentException
{
    public static function connectionDsnCantBeEmpty(): self
    {
        return new self('Connection DSN can\'t be empty');
    }

    public static function incorrectScheme(): self
    {
        return new self('Connection DSN must start with tcp:// or unix://');
    }

    public static function incorrectDSN(string $dsn): self
    {
        return new self(\sprintf('Can\'t parse specified connection DSN (%s)', $dsn));
    }
}
