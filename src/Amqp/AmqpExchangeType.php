<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Amqp;

enum AmqpExchangeType: string
{
    case FANOUT = 'fanout';
    case DIRECT = 'direct';
    case TOPIC = 'topic';
    case HEADERS = 'headers';
    case DELAYED = 'x-delayed-message';
}
