<?php

/**
 * AMQP transport implementation.
 *
 * @author  Konstantin  Grachev <me@grachevko.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Nsq;

use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Nsq\Exceptions\IncorrectChannelName;

/**
 * Which channel the message will be sent to.
 *
 * @psalm-immutable
 */
final class NsqTransportLevelDestination implements DeliveryDestination
{
    /**
     * @psalm-readonly
     * @psalm-var non-empty-string
     *
     * @var string
     */
    public $channel;

    /**
     * @throws \ServiceBus\Transport\Nsq\Exceptions\IncorrectChannelName
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
