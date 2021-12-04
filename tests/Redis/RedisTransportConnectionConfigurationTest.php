<?php

/** @noinspection PhpUnhandledExceptionInspection */

/**
 * Redis transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Transport\Tests\Redis;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Common\Exceptions\IncorrectConnectionParameters;
use ServiceBus\Transport\Redis\RedisTransportConnectionConfiguration;

/**
 *
 */
final class RedisTransportConnectionConfigurationTest extends TestCase
{
    /**
     * @test
     */
    public function successCreate(): void
    {
        $config = new RedisTransportConnectionConfiguration(
            'tcp://test-host:7000?timeout=-1&password=qwerty'
        );

        self::assertSame('test-host', $config->host);
        self::assertSame(7000, $config->port);
        self::assertSame(5, $config->timeout);
        self::assertSame('qwerty', $config->password);
    }

    /**
     * @test
     */
    public function emptyDSN(): void
    {
        $this->expectException(IncorrectConnectionParameters::class);
        $this->expectExceptionMessage('Connection DSN can\'t be empty');

        new RedisTransportConnectionConfiguration('');
    }

    /**
     * @test
     */
    public function withoutScheme(): void
    {
        $this->expectException(IncorrectConnectionParameters::class);
        $this->expectExceptionMessage('Connection DSN must start with tcp:// or unix://');

        new RedisTransportConnectionConfiguration('test-host:7000');
    }

    /**
     * @test
     */
    public function incorrectDSN(): void
    {
        $this->expectException(IncorrectConnectionParameters::class);
        $this->expectExceptionMessage('Can\'t parse specified connection DSN (tcp:///example.org:80)');

        new RedisTransportConnectionConfiguration('tcp:///example.org:80');
    }
}
