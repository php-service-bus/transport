<?php

/**
 * AMQP transport implementation.
 *
 * @author  Konstantin  Grachev <me@grachevko.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Nsq\Exceptions;

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
