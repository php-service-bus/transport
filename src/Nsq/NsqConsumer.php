<?php

/**
 * AMQP transport implementation.
 *
 * @author  Konstantin  Grachev <me@grachevko.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Nsq;

use Amp\Socket\ConnectException;
use Nsq\Config\ClientConfig;
use Nsq\Consumer;
use Nsq\Message;
use ServiceBus\Transport\Common\Exceptions\ConnectionFail;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function Amp\asyncCall;
use function Amp\call;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function ServiceBus\Common\jsonDecode;
use function ServiceBus\Common\uuid;

/**
 * @internal
 */
final class NsqConsumer
{
    /**
     * @var NsqChannel
     */
    private $channel;

    /**
     * @var NsqTransportConnectionConfiguration
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Consumer|null
     */
    private $subscribeClient;

    public function __construct(
        NsqChannel $channel,
        NsqTransportConnectionConfiguration $config,
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
                    $this->logger->debug('Creates new consumer for channel "{channelName}" ', [
                         'channelName' => $this->channel->name,
                     ]);

                    $this->subscribeClient = new Consumer(
                        $this->config->toString(),
                        $this->channel->toString(),
                        'php-service-bus',
                        function (Message $message) use ($onMessage): void
                        {
                            self::handleMessage(
                                $message,
                                $this->channel->toString(),
                                $onMessage,
                            );
                        },
                        new ClientConfig(),
                        $this->logger,
                    );

                    try
                    {
                        yield $this->subscribeClient->connect();
                    }
                    catch (ConnectException $e)
                    {
                        throw ConnectionFail::fromThrowable($e);
                    }
                }
            }
        );
    }

    /**
     * Call message handler.
     *
     * @throws \Throwable json decode failed
     */
    private static function handleMessage(Message $message, string $onChannel, callable $onMessage): void
    {
        $decoded = jsonDecode($message->body);

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
                new NsqIncomingPackage(
                    $message,
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
            new NsqIncomingPackage(
                $message,
                messageId: uuid(),
                traceId: uuid(),
                payload: $message->body,
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

                /** @psalm-suppress InternalMethod */
                $this->subscribeClient->close();

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
