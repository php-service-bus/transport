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

final class IncorrectChannelName extends \InvalidArgumentException
{
    public static function emptyChannelName(): self
    {
        return new self('Channel name can\'t be empty');
    }
}
