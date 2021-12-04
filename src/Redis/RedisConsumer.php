<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Redis;

use Amp\Promise;
use Amp\Redis\Config;
use Amp\Redis\Subscriber;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Common\Exceptions\ConnectionFail;
use function Amp\asyncCall;
use function Amp\call;
use function ServiceBus\Common\throwableDetails;

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
        RedisChannel                          $channel,
        RedisTransportConnectionConfiguration $config,
        ?LoggerInterface                      $logger = null
    ) {
        $this->channel = $channel;
        $this->config  = $config;
        $this->logger  = $logger ?? new NullLogger();
    }

    /**
     * Listen channel messages.
     *
     * @psalm-param callable(RedisIncomingPackage):\Generator $onMessage
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
                        /** @psalm-var string $jsonMessage */
                        $jsonMessage = $subscription->getCurrent();

                        $receivedPayload = new RedisReceivedPayload($jsonMessage);
                        $messageData     = $receivedPayload->parse();

                        asyncCall(
                            $onMessage,
                            new RedisIncomingPackage(
                                messageId: $messageData['messageId'],
                                traceId: $messageData['traceId'],
                                payload: $messageData['body'],
                                headers: $messageData['headers'],
                                fromChannel: $this->channel->name
                            )
                        );
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

                $this->subscribeClient = null;

                $this->logger->debug('Subscription canceled', ['channelName' => $this->channel->name]);
            }
        );
    }
}
