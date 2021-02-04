<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Common;

/**
 * Where the message will be sent within the specific transport.
 *
 * Marker interface
 */
interface DeliveryDestination
{
}
