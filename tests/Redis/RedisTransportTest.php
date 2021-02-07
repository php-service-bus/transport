<?php

/**
 * Redis transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Redis;

use Symfony\Component\Uid\Uuid;
use function ServiceBus\Common\uuid;
use Amp\Loop;
use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Common\Exceptions\ConnectionFail;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Redis\RedisChannel;
use ServiceBus\Transport\Redis\RedisIncomingPackage;
use ServiceBus\Transport\Redis\RedisTransport;
use ServiceBus\Transport\Redis\RedisTransportConnectionConfiguration;
use ServiceBus\Transport\Redis\RedisTransportLevelDestination;

/**
 *
 */
final class RedisTransportTest extends TestCase
{
    /** @var RedisTransportConnectionConfiguration */
    private $config;

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new RedisTransportConnectionConfiguration((string) \getenv('REDIS_CONNECTION_DSN'));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->config);
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function flow(): void
    {
        Loop::run(
            function (): \Generator
            {
                $transport = new RedisTransport($this->config, null);

                yield $transport->consume(
                    static function (RedisIncomingPackage $message) use (&$messages, $transport): \Generator
                    {
                        self::assertInstanceOf(RedisIncomingPackage::class, $message);
                        self::assertTrue(Uuid::isValid($message->id()));

                        $messages[] = $message->payload();

                        if (2 === \count($messages))
                        {
                            self::assertSame(['qwerty.message', 'root.message'], $messages);

                            yield $transport->stop();

                            Loop::stop();
                        }
                    },
                    new RedisChannel('qwerty'),
                    new  RedisChannel('root')
                );

                yield $transport->send(
                    new  OutboundPackage('qwerty.message', [], new RedisTransportLevelDestination('qwerty'))
                );

                yield $transport->send(
                    new OutboundPackage('root.message', [], new RedisTransportLevelDestination('root'))
                );
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     *
     */
    public function subscribeWithWrongConnectionData(): void
    {
        $this->expectException(ConnectionFail::class);
        $this->expectExceptionMessage('Failed to connect to redis instance (tcp://localhost:1000)');

        Loop::run(
            static function (): \Generator
            {
                $config = new RedisTransportConnectionConfiguration('tcp://localhost:1000');

                $transport = new RedisTransport($config);

                yield $transport->consume(
                    static function (): void
                    {
                    },
                    new  RedisChannel('root')
                );
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function disconnectWithoutConnections(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield (new RedisTransport($this->config))->disconnect();
                Loop::stop();
            }
        );
    }
}
