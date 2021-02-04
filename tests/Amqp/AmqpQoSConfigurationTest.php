<?php

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Amqp;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Amqp\AmqpQoSConfiguration;

/**
 *
 */
final class AmqpQoSConfigurationTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     */
    public function successCreate(): void
    {
        $qos = new AmqpQoSConfiguration(1, 6, true);

        self::assertSame(1, $qos->size);
        self::assertSame(6, $qos->count);
        self::assertTrue($qos->global);
    }
}
