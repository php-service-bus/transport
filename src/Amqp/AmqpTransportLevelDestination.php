<?php

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Amqp;

use ServiceBus\Transport\Amqp\Exceptions\IncorrectDestinationExchange;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Which exchange (and with which key) the message will be sent to.
 *
 * @psalm-readonly
 */
final class AmqpTransportLevelDestination implements DeliveryDestination
{
    /** @var string */
    public $exchange;

    /** @var string|null */
    public $routingKey;

    /**
     * @throws \ServiceBus\Transport\Amqp\Exceptions\IncorrectDestinationExchange
     */
    public function __construct(string $exchange, ?string $routingKey = null)
    {
        if ($exchange === '')
        {
            throw IncorrectDestinationExchange::destinationExchangeCantBeEmpty();
        }

        $this->exchange   = $exchange;
        $this->routingKey = $routingKey;
    }
}
