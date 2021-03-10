<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * AMQP transport implementation.
 *
 * @author  Konstantin  Grachev <me@grachevko.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Nsq;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Nsq\Exceptions\IncorrectConnectionParameters;
use ServiceBus\Transport\Nsq\NsqTransportConnectionConfiguration;

/**
 *
 */
final class NsqTransportConnectionConfigurationTest extends TestCase
{
    /**
     * @test
     */
    public function successCreate(): void
    {
        $config = new NsqTransportConnectionConfiguration(
            'tcp://test-host:7000?timeout=-1&password=qwerty'
        );

        self::assertSame('test-host', $config->host);
        self::assertSame(7000, $config->port);
        self::assertSame(5, $config->timeout);
    }

    /**
     * @test
     */
    public function emptyDSN(): void
    {
        $this->expectException(IncorrectConnectionParameters::class);
        $this->expectExceptionMessage('Connection DSN can\'t be empty');

        new NsqTransportConnectionConfiguration('');
    }

    /**
     * @test
     */
    public function withoutScheme(): void
    {
        $this->expectException(IncorrectConnectionParameters::class);
        $this->expectExceptionMessage('Connection DSN must start with tcp:// or unix://');

        new NsqTransportConnectionConfiguration('test-host:7000');
    }

    /**
     * @test
     */
    public function incorrectDSN(): void
    {
        $this->expectException(IncorrectConnectionParameters::class);
        $this->expectExceptionMessage('Can\'t parse specified connection DSN (tcp:///example.org:80)');

        new NsqTransportConnectionConfiguration('tcp:///example.org:80');
    }
}
