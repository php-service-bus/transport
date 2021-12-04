<?php

/** @noinspection PhpUnhandledExceptionInspection */

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Transport\Tests\Amqp;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Amqp\Exceptions\IncorrectDestinationExchange;

/**
 *
 */
final class AmqpTransportLevelDestinationTest extends TestCase
{
    /**
     * @test
     */
    public function withEmptyExchangeName(): void
    {
        $this->expectException(IncorrectDestinationExchange::class);
        $this->expectExceptionMessage('Destination exchange name must be specified');

        new AmqpTransportLevelDestination('');
    }

    /**
     * @test
     */
    public function successCreate(): void
    {
        $destination = new AmqpTransportLevelDestination('qwerty', 'root');

        self::assertSame('qwerty', $destination->exchange);
        self::assertSame('root', $destination->routingKey);
    }
}
