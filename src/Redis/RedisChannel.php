<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Redis;

use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Redis\Exceptions\IncorrectChannelName;

/**
 * Channel.
 *
 * @psalm-immutable
 */
final class RedisChannel implements Queue
{
    /**
     * @psalm-readonly
     *
     * @var string
     */
    public $name;

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
