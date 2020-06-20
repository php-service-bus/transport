<?php

/**
 * Redis transport implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Redis;

use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Redis\Exceptions\IncorrectChannelName;

/**
 * Channel.
 *
 * @psalm-readonly
 */
final class RedisChannel implements Queue
{
    /** @var string  */
    public $name;

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return $this->name;
    }

    /**
     * @throws \ServiceBus\Transport\Redis\Exceptions\IncorrectChannelName
     */
    public function __construct(string $channel)
    {
        if ($channel === '')
        {
            throw IncorrectChannelName::emptyChannelName();
        }

        $this->name = $channel;
    }
}
