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

use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Redis\Exceptions\IncorrectChannelName;

/**
 * Which channel the message will be sent to.
 *
 * @psalm-readonly
 */
final class RedisTransportLevelDestination implements DeliveryDestination
{
    /** @var string */
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
