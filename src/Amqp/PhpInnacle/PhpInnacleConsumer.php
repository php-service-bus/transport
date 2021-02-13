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

use ServiceBus\Transport\Common\Package\IncomingPackage;
use function Amp\asyncCall;
use function Amp\call;
use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use PHPinnacle\Ridge\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Amqp\AmqpQueue;
use function ServiceBus\Common\throwableMessage;
use function ServiceBus\Common\uuid;

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
     * @var string|null
     */
    private $tag;

    public function __construct(AmqpQueue $queue, Channel $channel, ?LoggerInterface $logger = null)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->tag = \sha1(\random_bytes(16));

        $this->queue   = $queue;
        $this->channel = $channel;
        $this->logger  = $logger ?? new NullLogger();
    }

    /**
     * Listen queue messages.
     *
     * @psalm-param callable(PhpInnacleIncomingPackage):\Generator $onMessageReceived
     *
     * @return Promise It does not return any result
     */
    public function listen(callable $onMessageReceived): Promise
    {
        $this->logger->debug('Creates new consumer on channel for queue "{queue}" with tag "{consumerTag}"', [
            'queue'       => $this->queue->name,
            'consumerTag' => $this->tag,
        ]);

        return $this->channel->consume(
            callback: $this->createMessageHandler($onMessageReceived),
            queue: $this->queue->name,
            consumerTag: (string) $this->tag,
            noLocal: false,
            noAck: false,
            exclusive: false,
            noWait: true
        );
    }

    /**
     * Stop watching the queue.
     *
     * @return Promise It does not return any result
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
                    messageId: self::extractUuidHeader(IncomingPackage::HEADER_MESSAGE_ID, $message),
                    traceId: self::extractUuidHeader(IncomingPackage::HEADER_TRACE_ID, $message),
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

    private static function extractUuidHeader(string $key, Message $message): string
    {
        $id  = (string) ($message->headers[$key] ?? uuid());
        $key = $key !== '' ? $key : uuid();

        unset($message->headers[$key]);

        return $id;
    }
}
