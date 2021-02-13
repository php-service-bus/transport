<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Redis;

use ServiceBus\Transport\Common\Package\IncomingPackage;
use function Amp\asyncCall;
use function Amp\call;
use Amp\Promise;
use Amp\Redis\Config;
use Amp\Redis\Subscriber;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Common\Exceptions\ConnectionFail;
use function ServiceBus\Common\jsonDecode;
use function ServiceBus\Common\throwableDetails;
use function ServiceBus\Common\uuid;

/**
 * @internal
 */
final class RedisConsumer
{
    /**
     * @var RedisChannel
     */
    private $channel;

    /**
     * @var RedisTransportConnectionConfiguration
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Subscriber|null
     */
    private $subscribeClient;

    public function __construct(
        RedisChannel $channel,
        RedisTransportConnectionConfiguration $config,
        ?LoggerInterface $logger = null
    ) {
        $this->channel = $channel;
        $this->config  = $config;
        $this->logger  = $logger ?? new NullLogger();
    }

    /**
     * Listen channel messages.
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail Connection refused
     */
    public function listen(callable $onMessage): Promise
    {
        return call(
            function () use ($onMessage): \Generator
            {
                if ($this->subscribeClient === null)
                {
                    $this->subscribeClient = new Subscriber(Config::fromUri($this->config->toString()));
                }

                $this->logger->debug('Creates new consumer for channel "{channelName}" ', [
                    'channelName' => $this->channel->name,
                ]);

                try
                {
                    /** @var \Amp\Redis\Subscription $subscription */
                    $subscription = yield $this->subscribeClient->subscribe($this->channel->toString());
                }
                catch (\Throwable $throwable)
                {
                    throw ConnectionFail::fromThrowable($throwable);
                }

                while (yield $subscription->advance())
                {
                    try
                    {
                        /** @var string $jsonMessage */
                        $jsonMessage = $subscription->getCurrent();

                        self::handleMessage($jsonMessage, $this->channel->name, $onMessage);
                    }
                    // @codeCoverageIgnoreStart
                    catch (\Throwable $throwable)
                    {
                        $this->logger->error('Emit package failed: {throwableMessage} ', throwableDetails($throwable));
                    }
                    // @codeCoverageIgnoreEnd
                }
            }
        );
    }

    /**
     * Call message handler.
     *
     * @throws \Throwable json decode failed
     */
    private static function handleMessage(string $messagePayload, string $onChannel, callable $onMessage): void
    {
        $decoded = jsonDecode($messagePayload);

        if (\count($decoded) === 2)
        {
            /**
             * @psalm-var string                          $body
             * @psalm-var array<string, string|int|float> $headers
             */
            [$body, $headers] = $decoded;

            $messageId = self::extractUuidHeader(IncomingPackage::HEADER_MESSAGE_ID, $headers);
            $traceId   = self::extractUuidHeader(IncomingPackage::HEADER_TRACE_ID, $headers);

            /** @psalm-suppress MixedArgumentTypeCoercion */
            asyncCall(
                $onMessage,
                new RedisIncomingPackage(
                    messageId: $messageId,
                    traceId: $traceId,
                    payload: $body,
                    headers: $headers,
                    fromChannel: $onChannel
                )
            );

            return;
        }

        /**
         * Message without headers.
         *
         * @psalm-suppress MixedArgumentTypeCoercion
         */
        asyncCall(
            $onMessage,
            new RedisIncomingPackage(
                messageId: uuid(),
                traceId: uuid(),
                payload: $messagePayload,
                headers: [],
                fromChannel: $onChannel
            )
        );
    }

    /**
     * Stop watching the channel.
     *
     * @return Promise It does not return any result
     */
    public function stop(): Promise
    {
        return call(
            function (): void
            {
                if ($this->subscribeClient === null)
                {
                    return;
                }

                $this->subscribeClient = null;

                $this->logger->debug('Subscription canceled', ['channelName' => $this->channel->name]);
            }
        );
    }

    private static function extractUuidHeader(string $key, array &$headers): string
    {
        $id  = (string) ($headers[$key] ?? uuid());
        $key = $key !== '' ? $key : uuid();

        unset($headers[$key]);

        return $id;
    }
}
