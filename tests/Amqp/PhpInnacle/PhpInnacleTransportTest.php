<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHPinnacle RabbitMQ adapter.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Amqp\PhpInnacle;

use ServiceBus\Transport\Amqp\PhpInnacle\PhpInnacleIncomingPackage;
use ServiceBus\Transport\Amqp\PhpInnacle\PhpInnacleTransport;
use function Amp\Promise\wait;
use function ServiceBus\Common\readReflectionPropertyValue;
use Amp\Loop;
use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use ServiceBus\Transport\Amqp\AmqpExchange;
use ServiceBus\Transport\Amqp\AmqpQueue;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\TopicBind;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class PhpInnacleTransportTest extends TestCase
{
    /**
     * @var PhpInnacleTransport
     */
    private $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = new PhpInnacleTransport(
            new AmqpConnectionConfiguration((string) \getenv('TRANSPORT_CONNECTION_DSN'))
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        try
        {
            wait($this->transport->connect());

            /** @var \PHPinnacle\Ridge\Client|null $client */
            $client = readReflectionPropertyValue($this->transport, 'client');

            $channel = wait($client->channel());

            wait($channel->exchangeDelete('createExchange'));
            wait($channel->queueDelete('createQueue'));

            wait($channel->exchangeDelete('createExchange2'));
            wait($channel->queueDelete('createQueue2'));

            wait($channel->exchangeDelete('consume'));
            wait($channel->queueDelete('consume.messages'));

            wait($this->transport->disconnect());
        }
        catch (\Throwable)
        {
        }
    }

    /**
     * @test
     */
    public function connect(): void
    {
        wait($this->transport->connect());
    }

    /**
     * @test
     */
    public function createExchange(): void
    {
        wait($this->transport->createTopic(AmqpExchange::topic('createExchange')));
    }

    /**
     * @test
     */
    public function createQueue(): void
    {
        wait($this->transport->createQueue(AmqpQueue::default('createQueue')));
    }

    /**
     * @test
     */
    public function bindTopic(): void
    {
        wait(
            $this->transport->createTopic(
                AmqpExchange::topic('createExchange'),
                new TopicBind(
                    AmqpExchange::topic('createExchange2'),
                    'qwerty'
                )
            )
        );
    }

    /**
     * @test
     */
    public function bindQueue(): void
    {
        wait(
            $this->transport->createQueue(
                AmqpQueue::default('createQueue'),
                new  QueueBind(
                    AmqpExchange::topic('createExchange2'),
                    'qwerty'
                )
            )
        );
    }

    /**
     * @test
     */
    public function consume(): void
    {
        Loop::run(
            function (): \Generator
            {
                $exchange = AmqpExchange::direct('consume');
                $queue    = AmqpQueue::default('consume.messages');

                yield $this->transport->createTopic($exchange);
                yield $this->transport->createQueue($queue, new QueueBind($exchange, 'consume'));

                yield $this->transport->send(
                    new  OutboundPackage(
                        uuid(),
                        'somePayload',
                        ['key' => 'value'],
                        new AmqpTransportLevelDestination('consume', 'consume')
                    )
                );

                yield $this->transport->consume(
                    function (PhpInnacleIncomingPackage $package): \Generator
                    {
                        self::assertInstanceOf(PhpInnacleIncomingPackage::class, $package);
                        self::assertSame('somePayload', $package->payload());
                        self::assertCount(1, $package->headers());

                        yield $this->transport->disconnect();
                    },
                    $queue
                );
            }
        );
    }

    /**
     * @test
     */
    public function bulkPublish(): void
    {
        Loop::run(
            function (): \Generator
            {
                $exchange = AmqpExchange::direct('consume');
                $queue    = AmqpQueue::default('consume.messages');

                yield $this->transport->createTopic($exchange);
                yield $this->transport->createQueue($queue, new QueueBind($exchange, 'consume'));

                yield $this->transport->send(
                    new  OutboundPackage(
                        uuid(),
                        'somePayload1',
                        ['key' => 'value'],
                        new AmqpTransportLevelDestination('consume', 'consume')
                    ),
                    new  OutboundPackage(
                        uuid(),
                        'somePayload2',
                        ['key' => 'value2'],
                        new AmqpTransportLevelDestination('consume', 'consume')
                    )
                );

                $index = 1;

                yield $this->transport->consume(
                    function (PhpInnacleIncomingPackage $package) use (&$index): \Generator
                    {
                        self::assertInstanceOf(PhpInnacleIncomingPackage::class, $package);
                        self::assertSame('somePayload' . $index, $package->payload());
                        self::assertCount(1, $package->headers());

                        $index++;

                        if ($index === 2)
                        {
                            yield $this->transport->disconnect();
                        }
                    },
                    $queue
                );
            }
        );
    }
}
