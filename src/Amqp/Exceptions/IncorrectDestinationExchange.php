<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Amqp\Exceptions;

/**
 *
 */
final class IncorrectDestinationExchange extends \InvalidArgumentException
{
    public static function destinationExchangeCantBeEmpty(): self
    {
        return new self('Destination exchange name must be specified');
    }
}
