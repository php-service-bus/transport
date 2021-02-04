<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Amqp\PhpInnacle;

use function Amp\call;
use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use PHPinnacle\Ridge\Client;
use PHPinnacle\Ridge\Config;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use ServiceBus\Transport\Amqp\AmqpExchange;
use ServiceBus\Transport\Amqp\AmqpQoSConfiguration;
use ServiceBus\Transport\Amqp\AmqpQueue;
use ServiceBus\Transport\Common\Exceptions\ConnectionFail;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\Topic;
use ServiceBus\Transport\Common\TopicBind;
use ServiceBus\Transport\Common\Transport;

/**
 *
 */
final class PhpInnacleTransport implements Transport
{
    /**
     * Client for work with AMQP protocol.
     *
     * @var Client
     */
    private $client;

    /**
     * Null if not connected.
     *
     * @var Channel|null
     */
    private $channel;

    /**
     * @var PhpInnaclePublisher|null
     */
    private $publisher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @psalm-var array<string, \ServiceBus\Transport\Amqp\PhpInnacle\PhpInnacleConsumer>
     *
     * @var \ServiceBus\Transport\Amqp\PhpInnacle\PhpInnacleConsumer[]
     */
    private $consumers = [];

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        AmqpConnectionConfiguration $connectionConfig,
        AmqpQoSConfiguration $qosConfig = null,
        ?LoggerInterface $logger = null
    ) {
        $qosConfig = $qosConfig ?? new AmqpQoSConfiguration();

        $this->logger = $logger ?? new NullLogger();
        $this->config = $this->adaptConfig($connectionConfig, $qosConfig);

        $this->client = new Client($this->config);
    }

    public function connect(): Promise
    {
        return call(
            function (): \Generator
            {
                if ($this->client->isConnected())
                {
                    return;
                }

                try
                {
                    yield $this->client->connect();

                    $channel = yield $this->client->channel();

                    $this->channel = $channel;

                    $this->logger->info('Connected to broker', [
                        'host'  => $this->config->host,
                        'port'  => $this->config->port,
                        'vhost' => $this->config->vhost,
                    ]);
                }
                catch (\Throwable $throwable)
                {
                    throw new ConnectionFail(
                        \sprintf(
                            'Can\'t connect to %s:%d (vhost: %s) with credentials %s:%s',
                            $this->config->host,
                            $this->config->port,
                            $this->config->vhost,
                            $this->config->user,
                            $this->config->pass
                        ),
                        (int) $throwable->getCode(),
                        $throwable
                    );
                }
            }
        );
    }

    public function disconnect(): Promise
    {
        return call(
            function (): \Generator
            {
                try
                {
                    if ($this->client->isConnected())
                    {
                        yield $this->client->disconnect();
                    }
                }
                catch (\Throwable)
                {
                    /** Not interested */
                }

                $this->logger->info('Disconnect from broker', [
                    'host'  => $this->config->host,
                    'port'  => $this->config->port,
                    'vhost' => $this->config->vhost,
                ]);
            }
        );
    }

    public function consume(callable $onMessage, Queue ...$queues): Promise
    {
        return call(
            function () use ($queues, $onMessage): \Generator
            {
                yield $this->connect();

                $channel = yield $this->client->channel();

                /** @var AmqpQueue $queue */
                foreach ($queues as $queue)
                {
                    $this->logger->info('Starting a subscription to the "{queueName}" queue', [
                        'host'      => $this->config->host,
                        'port'      => $this->config->port,
                        'vhost'     => $this->config->vhost,
                        'queueName' => $queue->name,
                        'channel'   => $channel->id(),
                    ]);

                    $consumer = new PhpInnacleConsumer($queue, $channel, $this->logger);

                    $consumer->listen($onMessage);

                    $this->consumers[$queue->name] = $consumer;
                }
            }
        );
    }

    public function stop(): Promise
    {
        return call(
            function (): \Generator
            {
                /**
                 * @var string             $queueName
                 * @var PhpInnacleConsumer $consumer
                 */
                foreach ($this->consumers as $queueName => $consumer)
                {
                    $this->logger->info('Completing the subscription to the "{queueName}" queue', [
                        'host'      => $this->config->host,
                        'port'      => $this->config->port,
                        'vhost'     => $this->config->vhost,
                        'queueName' => $queueName,
                    ]);

                    if (isset($this->consumers[$queueName]))
                    {
                        /** @var PhpInnacleConsumer $consumer */
                        $consumer = $this->consumers[$queueName];

                        yield $consumer->stop();

                        unset($this->consumers[$queueName]);
                    }
                }
            }
        );
    }

    public function send(OutboundPackage ...$outboundPackages): Promise
    {
        return call(
            function () use ($outboundPackages): \Generator
            {
                if (\count($outboundPackages) === 0)
                {
                    return;
                }

                yield $this->connect();

                /** @var Channel $channel */
                $channel = $this->channel;

                if ($this->publisher === null)
                {
                    $this->publisher = new PhpInnaclePublisher($channel, $this->logger);
                }

                if (\count($outboundPackages) === 1)
                {
                    yield $this->publisher->process(
                        $outboundPackages[\array_key_first($outboundPackages)]
                    );

                    return;
                }

                yield $this->publisher->processBulk(...$outboundPackages);
            }
        );
    }

    public function createTopic(Topic $topic, TopicBind ...$binds): Promise
    {
        return call(
            function () use ($topic, $binds): \Generator
            {
                /** @var AmqpExchange $amqpExchange */
                $amqpExchange = $topic;

                /**
                 * @var \ServiceBus\Transport\Common\TopicBind[]                   $binds
                 * @psalm-var array<mixed, \ServiceBus\Transport\Common\TopicBind> $binds
                 */
                yield $this->connect();

                /** @var Channel $channel */
                $channel = $this->channel;

                $configurator = new PhpInnacleConfigurator($channel);

                yield $configurator->doCreateExchange($amqpExchange);
                yield $configurator->doBindExchange($amqpExchange, $binds);
            }
        );
    }

    public function createQueue(Queue $queue, QueueBind ...$binds): Promise
    {
        return call(
            function () use ($queue, $binds): \Generator
            {
                /** @var AmqpQueue $amqpQueue */
                $amqpQueue = $queue;

                /**
                 * @var \ServiceBus\Transport\Common\QueueBind[]                   $binds
                 * @psalm-var array<mixed, \ServiceBus\Transport\Common\QueueBind> $binds
                 */
                yield $this->connect();

                /** @var Channel $channel */
                $channel = $this->channel;

                $configurator = new PhpInnacleConfigurator($channel);

                yield $configurator->doCreateQueue($amqpQueue);
                yield $configurator->doBindQueue($amqpQueue, $binds);
            }
        );
    }

    private function adaptConfig(
        AmqpConnectionConfiguration $connectionConfiguration,
        AmqpQoSConfiguration $qoSConfiguration
    ): Config {
        $connectionDSN = \sprintf(
            '%s://%s:%s@%s:%s/%s?%s',
            $connectionConfiguration->parameters['scheme'],
            $connectionConfiguration->parameters['user'],
            $connectionConfiguration->parameters['password'],
            $connectionConfiguration->parameters['host'],
            $connectionConfiguration->parameters['port'],
            \ltrim($connectionConfiguration->parameters['vhost'], '/'),
            http_build_query(
                [
                    'timeout'    => $connectionConfiguration->parameters['timeout'],
                    'heartbeat'  => $connectionConfiguration->parameters['heartbeat'],
                    'qos_size'   => $qoSConfiguration->size,
                    'qos_count'  => $qoSConfiguration->count,
                    'qos_global' => (int) $qoSConfiguration->global
                ]
            )
        );

        return Config::parse($connectionDSN);
    }
}
