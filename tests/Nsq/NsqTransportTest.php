<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Nsq transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Nsq;

use function ServiceBus\Common\uuid;
use Symfony\Component\Uid\Uuid;
use Amp\Loop;
use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Common\Exceptions\ConnectionFail;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Nsq\NsqChannel;
use ServiceBus\Transport\Nsq\NsqIncomingPackage;
use ServiceBus\Transport\Nsq\NsqTransport;
use ServiceBus\Transport\Nsq\NsqTransportConnectionConfiguration;
use ServiceBus\Transport\Nsq\NsqTransportLevelDestination;

/**
 *
 */
final class NsqTransportTest extends TestCase
{
    /**
     * @var NsqTransportConnectionConfiguration
     */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new NsqTransportConnectionConfiguration((string) \getenv('NSQ_CONNECTION_DSN'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->config);
    }

    /**
     * @test
     */
    public function flow(): void
    {
        Loop::run(
            function (): \Generator
            {
                $transport = new NsqTransport($this->config, null);

                yield $transport->consume(
                    static function (NsqIncomingPackage $message) use (&$messages, $transport): \Generator
                    {
                        self::assertInstanceOf(NsqIncomingPackage::class, $message);
                        self::assertTrue(Uuid::isValid($message->id()));

                        $messages[] = $message->payload();

                        if (\count($messages) === 2)
                        {
                            self::assertSame(['qwerty.message', 'root.message'], $messages);

                            yield $transport->stop();

                            Loop::stop();
                        }
                    },
                    new NsqChannel('qwerty'),
                    new  NsqChannel('root')
                );

                yield $transport->send(
                    new  OutboundPackage(uuid(), 'qwerty.message', [], new NsqTransportLevelDestination('qwerty'))
                );

                yield $transport->send(
                    new OutboundPackage(uuid(), 'root.message', [], new NsqTransportLevelDestination('root'))
                );
            }
        );
    }

    /**
     * @test
     *
     */
    public function subscribeWithWrongConnectionData(): void
    {
        $this->expectException(ConnectionFail::class);
        $this->expectExceptionMessage('Connection to tcp://localhost:1000 refused; previous attempts: tcp://localhost:1000 (connection refused)');

        Loop::run(
            static function (): \Generator
            {
                $config = new NsqTransportConnectionConfiguration('tcp://localhost:1000');

                $transport = new NsqTransport($config);

                yield $transport->consume(
                    static function (): void
                    {
                    },
                    new  NsqChannel('root')
                );
            }
        );
    }

    /**
     * @test
     */
    public function disconnectWithoutConnections(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield (new NsqTransport($this->config))->disconnect();
                Loop::stop();
            }
        );
    }
}
