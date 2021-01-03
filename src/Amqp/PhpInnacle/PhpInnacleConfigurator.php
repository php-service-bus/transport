<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Amqp\PhpInnacle;

use function Amp\call;
use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Amqp\AmqpExchange;
use ServiceBus\Transport\Amqp\AmqpQueue;
use ServiceBus\Transport\Common\Exceptions\BindFailed;
use ServiceBus\Transport\Common\Exceptions\CreateQueueFailed;
use ServiceBus\Transport\Common\Exceptions\CreateTopicFailed;
use function ServiceBus\Common\throwableDetails;
use function ServiceBus\Common\throwableMessage;

/**
 * Creating exchangers\queues and bind them.
 *
 * @internal
 */
final class PhpInnacleConfigurator
{
    /** @var Channel */
    private $channel;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Channel $channel, ?LoggerInterface $logger = null)
    {
        $this->channel = $channel;
        $this->logger  = $logger ?? new NullLogger();

        if (\extension_loaded('ext-buffer') === false)
        {
            $this->logger->info(
                'Install a "ext-buffer" extension to improve performance (https://github.com/phpinnacle/ext-buffer)'
            );
        }
    }

    /**
     * Execute queue creation.
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\CreateQueueFailed
     */
    public function doCreateQueue(AmqpQueue $queue): Promise
    {
        return call(
            function () use ($queue): \Generator
            {
                try
                {
                    $this->logger->info('Creating "{queueName}" queue', ['queueName' => $queue->name]);

                    yield $this->channel->queueDeclare(
                        $queue->name,
                        $queue->passive,
                        $queue->durable,
                        $queue->exclusive,
                        $queue->autoDelete,
                        true,
                        $queue->arguments
                    );
                }
                catch (\Throwable $throwable)
                {
                    $this->logger->error(throwableMessage($throwable), throwableDetails($throwable));

                    throw CreateQueueFailed::fromThrowable($throwable);
                }
            }
        );
    }

    /**
     * Bind queue to exchange(s).
     *
     * @psalm-param  array<mixed, \ServiceBus\Transport\Common\QueueBind> $binds
     *
     * @param \ServiceBus\Transport\Common\QueueBind[] $binds
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\BindFailed
     */
    public function doBindQueue(AmqpQueue $queue, array $binds): Promise
    {
        return call(
            function () use ($queue, $binds): \Generator
            {
                try
                {
                    foreach ($binds as $bind)
                    {
                        /** @var AmqpExchange $destinationExchange */
                        $destinationExchange = $bind->destinationTopic;

                        yield $this->doCreateExchange($destinationExchange);

                        $this->logger->info(
                            'Linking "{queueName}" queue to the exchange "{exchangeName}" with the routing key "{routingKey}"',
                            [
                                'queueName'    => $queue->name,
                                'exchangeName' => $destinationExchange->name,
                                'routingKey'   => (string) $bind->routingKey,
                            ]
                        );

                        yield $this->channel->queueBind($queue->name, $destinationExchange->name, (string) $bind->routingKey);
                    }
                }
                catch (\Throwable $throwable)
                {
                    $this->logger->error($throwable->getMessage(), throwableDetails($throwable));

                    throw BindFailed::fromThrowable($throwable);
                }
            }
        );
    }

    /**
     * Execute exchange creation.
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\CreateTopicFailed
     */
    public function doCreateExchange(AmqpExchange $exchange): Promise
    {
        return call(
            function () use ($exchange): \Generator
            {
                try
                {
                    $this->logger->info('Creating "{exchangeName}" exchange', ['exchangeName' => $exchange->name]);

                    yield $this->channel->exchangeDeclare(
                        $exchange->name,
                        $exchange->type,
                        $exchange->passive,
                        $exchange->durable,
                        false,
                        false,
                        true,
                        $exchange->arguments
                    );
                }
                catch (\Throwable $throwable)
                {
                    $this->logger->error(throwableMessage($throwable), throwableDetails($throwable));

                    throw CreateTopicFailed::fromThrowable($throwable);
                }
            }
        );
    }

    /**
     * Bind exchange to another exchange(s).
     *
     * @psalm-param  array<mixed, \ServiceBus\Transport\Common\TopicBind> $binds
     *
     * @param \ServiceBus\Transport\Common\TopicBind[] $binds
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\BindFailed
     */
    public function doBindExchange(AmqpExchange $exchange, array $binds): Promise
    {
        return call(
            function () use ($exchange, $binds): \Generator
            {
                try
                {
                    foreach ($binds as $bind)
                    {
                        /** @var AmqpExchange $sourceExchange */
                        $sourceExchange = $bind->destinationTopic;

                        yield $this->doCreateExchange($sourceExchange);

                        $this->logger->info(
                            'Linking "{exchangeName}" exchange to the exchange "{destinationExchangeName}" with the routing key "{routingKey}"',
                            [
                                'queueName'               => $sourceExchange->name,
                                'destinationExchangeName' => $exchange->name,
                                'routingKey'              => (string) $bind->routingKey,
                            ]
                        );

                        yield $this->channel->exchangeBind($sourceExchange->name, $exchange->name, (string) $bind->routingKey);
                    }
                }
                catch (\Throwable $throwable)
                {
                    $this->logger->error($throwable->getMessage(), throwableDetails($throwable));

                    throw BindFailed::fromThrowable($throwable);
                }
            }
        );
    }
}
