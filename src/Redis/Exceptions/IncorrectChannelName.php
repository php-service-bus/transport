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
final class IncorrectChannelName extends \InvalidArgumentException
{
    public static function emptyChannelName(): self
    {
        return new self('Channel name can\'t be empty');
    }
}
