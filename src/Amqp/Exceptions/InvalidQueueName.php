<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Amqp\Exceptions;

/**
 *
 */
final class InvalidQueueName extends \InvalidArgumentException
{
    public static function nameCantBeEmpty(): self
    {
        return new self('Queue name must be specified');
    }

    public static function nameIsToLong(string $name): self
    {
        return new self(
            \sprintf('Queue name may be up to 255 bytes of UTF-8 characters (%d specified)', (string) \mb_strlen($name))
        );
    }
}
