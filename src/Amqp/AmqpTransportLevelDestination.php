<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Amqp;

use ServiceBus\Transport\Amqp\Exceptions\IncorrectDestinationExchange;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Which exchange (and with which key) the message will be sent to.
 */
final class AmqpTransportLevelDestination implements DeliveryDestination
{
    /**
     * @psalm-readonly
     *
     * @var string
     */
    public $exchange;

    /**
     * @psalm-readonly
     *
     * @var string|null
     */
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
