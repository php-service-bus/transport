<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Nsq;

use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Nsq\Exceptions\IncorrectChannelName;

/**
 * Channel.
 *
 * @psalm-immutable
 */
final class NsqChannel implements Queue
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
     * @throws \ServiceBus\Transport\Nsq\Exceptions\IncorrectChannelName
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
