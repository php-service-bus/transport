<?php

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Amqp;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use ServiceBus\Transport\Common\Exceptions\InvalidConnectionParameters;

/**
 *
 */
final class AmqpConnectionConfigurationTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     */
    public function createLocalhost(): void
    {
        $options = AmqpConnectionConfiguration::createLocalhost();

        static::assertSame(
            'amqp://guest:guest@localhost:5672?vhost=/&timeout=1&heartbeat=60.00',
            (string) $options
        );

        static::assertSame('localhost', $options->host());
        static::assertSame(5672, $options->port());
        static::assertSame('/', $options->virtualHost());
        static::assertSame('guest', $options->password());
        static::assertSame('guest', $options->user());
        static::assertSame(1.0, $options->timeout());
        static::assertSame(60.0, $options->heartbeatInterval());
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function parseDSN(): void
    {
        static::assertSame(
            AmqpConnectionConfiguration::createLocalhost()->heartbeatInterval(),
            (new AmqpConnectionConfiguration(
                'amqp://guest:guest@localhost:5672?vhost=/&timeout=1&heartbeat=60.00'
            ))->heartbeatInterval()
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function failedQuery(): void
    {
        $this->expectException(InvalidConnectionParameters::class);
        $this->expectExceptionMessage('Can\'t parse specified connection DSN (///example.org:80)');

        new AmqpConnectionConfiguration('///example.org:80');
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function emptyDSN(): void
    {
        $this->expectException(InvalidConnectionParameters::class);
        $this->expectExceptionMessage('Connection DSN can\'t be empty');

        new AmqpConnectionConfiguration('');
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function virtualHost(): void
    {
        $config = new  AmqpConnectionConfiguration(
            'amqp://guest:guest@localhost:5672?vhost=/test//my/vhost///&timeout=1&heartbeat=60.00'
        );

        static::assertSame('/test//my/vhost///', $config->virtualHost());
    }
}
