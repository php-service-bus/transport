<?php

/**
 * Redis transport implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

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
