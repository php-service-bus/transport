<?php

/**
 * AMQP transport implementation.
 *
 * @author  Konstantin  Grachev <me@grachevko.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Nsq;

use Amp\Socket\ConnectException;
use Nsq\Config\ClientConfig;
use Nsq\Consumer;
use Nsq\Message;
use ServiceBus\Transport\Common\Exceptions\ConnectionFail;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Amp\asyncCall;
use function Amp\call;
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
        NsqChannel                          $channel,
        NsqTransportConnectionConfiguration $config,
        ?LoggerInterface                    $logger = null
    ) {
        $this->channel = $channel;
        $this->config  = $config;
        $this->logger  = $logger ?? new NullLogger();
    }

    /**
     * Listen channel messages.
     *
     * @psalm-param callable(NsqIncomingPackage):\Generator $onMessage
     *
     * @psalm-return Promise<void>
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
                        address: $this->config->toString(),
                        topic: $this->channel->toString(),
                        channel: 'php-service-bus',
                        onMessage: function (Message $message) use ($onMessage): void
                        {
                            $this->handleMessage(
                                $message,
                                $this->channel->toString(),
                                $onMessage,
                            );
                        },
                        clientConfig: new ClientConfig(),
                        logger: $this->logger,
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
     * @psalm-param non-empty-string                        $onChannel
     * @psalm-param callable(NsqIncomingPackage):\Generator $onMessage
     *
     * @throws \Throwable json decode failed
     */
    private function handleMessage(Message $message, string $onChannel, callable $onMessage): void
    {
        $decodedPayload = $this->decodeMessageBody($message);

        $messageId = self::extractFromHeaders(
            key: IncomingPackage::HEADER_MESSAGE_ID,
            withDefault: uuid(),
            headers: $decodedPayload['headers']
        );

        $traceId = self::extractFromHeaders(
            key: IncomingPackage::HEADER_TRACE_ID,
            withDefault: uuid(),
            headers: $decodedPayload['headers']
        );

        asyncCall(
            $onMessage,
            new NsqIncomingPackage(
                $message,
                messageId: $messageId,
                traceId: $traceId,
                payload: $decodedPayload['body'],
                headers: $decodedPayload['headers'],
                fromChannel: $onChannel
            )
        );
    }

    /**
     * Stop watching the channel.
     *
     * @psalm-return Promise<void>
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

    /**
     * @psalm-return array{body:non-empty-string, headers: array<non-empty-string, int|float|string|null>}
     */
    private function decodeMessageBody(Message $message): array
    {
        $messageBody = $message->body;

        if (empty($messageBody))
        {
            throw new \LogicException('Received message payload cant be empty');
        }

        /**
         * @psalm-var array{
         *     0:string|non-empty-string,
         *     1:array<non-empty-string, int|float|string|null>|null
         * } $decodedMessageBody
         */
        $decodedMessageBody = jsonDecode($messageBody);


        if (empty($decodedMessageBody[0]))
        {
            throw new \LogicException('Received message payload cant be empty');
        }

        /** @psalm-var array<non-empty-string, int|float|string|null> $headers */
        $headers = $decodedMessageBody[1] ?? [];

        return [
            'body'    => $decodedMessageBody[0],
            'headers' => $headers
        ];
    }

    /**
     * @psalm-param non-empty-string $key
     * @psalm-param non-empty-string $withDefault
     *
     * @psalm-return non-empty-string
     */
    private static function extractFromHeaders(string $key, string $withDefault, array &$headers): string
    {
        $value = (string) $headers[$key];

        unset($headers[$key]);

        if (!empty($value))
        {
            return $value;
        }

        return $withDefault;
    }
}
