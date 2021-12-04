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

use Amp\Loop;
use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Redis\RedisChannel;
use ServiceBus\Transport\Redis\RedisConsumer;
use ServiceBus\Transport\Redis\RedisTransportConnectionConfiguration;

/**
 *
 */
final class RedisConsumerTest extends TestCase
{
    /**
     * @var RedisTransportConnectionConfiguration
     */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new RedisTransportConnectionConfiguration((string) \getenv('REDIS_CONNECTION_DSN'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->config);
    }

    /**
     * @test
     */
    public function disconnectWithoutConsuming(): void
    {
        Loop::run(
            function (): \Generator
            {
                $consumer = new RedisConsumer(new RedisChannel('qwerty'), $this->config);

                yield $consumer->stop();

                self::assertTrue(true);
            }
        );
    }
}
