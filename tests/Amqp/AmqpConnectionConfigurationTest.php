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
use ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use ServiceBus\Transport\Common\Exceptions\InvalidConnectionParameters;

/**
 *
 */
final class AmqpConnectionConfigurationTest extends TestCase
{
    public function createLocalhost(): void
    {
        $options = AmqpConnectionConfiguration::createLocalhost();

        self::assertSame(
            'amqp://guest:guest@localhost:5672?vhost=/&timeout=5000&heartbeat=60000',
            (string) $options
        );

        self::assertSame('localhost', $options->parameters['host']);
        self::assertSame(5672, $options->parameters['port']);
        self::assertSame('/', $options->parameters['vhost']);
        self::assertSame('guest', $options->parameters['password']);
        self::assertSame('guest', $options->parameters['user']);
        self::assertSame(5000, $options->parameters['timeout']);
        self::assertSame(60000, $options->parameters['heartbeat']);
    }

    /**
     * @test
     */
    public function parseDSN(): void
    {
        self::assertSame(
            AmqpConnectionConfiguration::createLocalhost()->parameters['heartbeat'],
            (new AmqpConnectionConfiguration(
                'amqp://guest:guest@localhost:5672?vhost=/&timeout=1&heartbeat=60000'
            ))->parameters['heartbeat']
        );
    }

    /**
     * @test
     */
    public function failedQuery(): void
    {
        $this->expectException(InvalidConnectionParameters::class);
        $this->expectExceptionMessage('Can\'t parse specified connection DSN (///example.org:80)');

        new AmqpConnectionConfiguration('///example.org:80');
    }

    /**
     * @test
     */
    public function emptyDSN(): void
    {
        $this->expectException(InvalidConnectionParameters::class);
        $this->expectExceptionMessage('Connection DSN can\'t be empty');

        new AmqpConnectionConfiguration('');
    }

    /**
     * @test
     */
    public function virtualHost(): void
    {
        $config = new  AmqpConnectionConfiguration(
            'amqp://guest:guest@localhost:5672?vhost=/test//my/vhost///&timeout=1&heartbeat=60.00'
        );

        self::assertSame('/test//my/vhost///', $config->parameters['vhost']);
    }
}
