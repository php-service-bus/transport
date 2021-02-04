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

use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Redis\Exceptions\IncorrectChannelName;

/**
 * Which channel the message will be sent to.
 *
 * @psalm-immutable
 */
final class RedisTransportLevelDestination implements DeliveryDestination
{
    /**
     * @psalm-readonly
     *
     * @var string
     */
    public $channel;

    /**
     * @throws \ServiceBus\Transport\Redis\Exceptions\IncorrectChannelName
     */
    public function __construct(string $channel)
    {
        if ($channel === '')
        {
            throw IncorrectChannelName::emptyChannelName();
        }

        $this->channel = $channel;
    }
}
