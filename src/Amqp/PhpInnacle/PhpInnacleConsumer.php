<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Amqp\PhpInnacle;

use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use PHPinnacle\Ridge\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Amqp\AmqpQueue;
use function Amp\asyncCall;
use function Amp\call;
use function ServiceBus\Common\throwableMessage;

/**
 * @internal
 */
final class PhpInnacleConsumer
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * Listen queue.
     *
     * @var AmqpQueue
     */
    private $queue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Consumer tag.
     *
     * @psalm-var non-empty-string|null
     *
     * @var string|null
     */
    private $tag;

    public function __construct(AmqpQueue $queue, Channel $channel, ?LoggerInterface $logger = null)
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @psalm-var     non-empty-string $consumerTag
         */
        $consumerTag = \sha1(\random_bytes(16));

        $this->tag = $consumerTag;

        $this->queue   = $queue;
        $this->channel = $channel;
        $this->logger  = $logger ?? new NullLogger();
    }

    /**
     * Listen queue messages.
     *
     * @psalm-param callable(PhpInnacleIncomingPackage):\Generator $onMessageReceived
     *
     * @psalm-return Promise<void>
     */
    public function listen(callable $onMessageReceived): Promise
    {
        return call(
            function () use ($onMessageReceived): \Generator
            {
                $this->logger->debug(
                    'Creates new consumer on channel for queue "{queue}" with tag "{consumerTag}"',
                    [
                    'queue'       => $this->queue->name,
                    'consumerTag' => $this->tag,
                ]
                );

                yield $this->channel->consume(
                    callback: $this->createMessageHandler($onMessageReceived),
                    queue: $this->queue->name,
                    consumerTag: (string) $this->tag,
                    noLocal: false,
                    noAck: false,
                    exclusive: false,
                    noWait: true
                );
            }
        );
    }

    /**
     * Stop watching the queue.
     *
     * @psalm-return Promise<void>
     */
    public function stop(): Promise
    {
        return call(
            function (): \Generator
            {
                if ($this->tag !== null)
                {
                    yield $this->channel->cancel($this->tag);
                }

                $this->logger->debug(
                    'Subscription canceled',
                    [
                        'queue'       => $this->queue->name,
                        'consumerTag' => $this->tag,
                    ]
                );

                $this->tag = null;
            }
        );
    }

    /**
     * Create listener callback.
     *
     * @psalm-param callable(PhpInnacleIncomingPackage):\Generator $onMessageReceived
     *
     * @psalm-return callable(Message, Channel):void
     */
    private function createMessageHandler($onMessageReceived): callable
    {
        return function (Message $message, Channel $channel) use ($onMessageReceived): void
        {
            try
            {
                $incomingPackage = new PhpInnacleIncomingPackage(
                    message: $message,
                    channel: $channel
                );

                $this->logger->debug('New message received from "{queueName}"', [
                    'queueName'         => $this->queue->name,
                    'messageId'         => $incomingPackage->id(),
                    'traceId'           => $incomingPackage->id(),
                    'rawMessagePayload' => $incomingPackage->payload(),
                    'rawMessageHeaders' => $incomingPackage->headers(),
                ]);

                asyncCall($onMessageReceived, $incomingPackage);

                unset($incomingPackage);
            }
            catch (\Throwable $throwable)
            {
                $this->logger->error(
                    'Error occurred: {throwableMessage}',
                    [
                        'throwableMessage'  => throwableMessage($throwable),
                        'throwablePoint'    => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine()),
                        'rawMessagePayload' => $message->content,
                    ]
                );
            }
        };
    }
}
