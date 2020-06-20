<?php

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

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
