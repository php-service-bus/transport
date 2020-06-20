<?php

/**
 * Redis transport implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Redis;

use function Amp\call;
use Amp\Promise;
use Amp\Redis\Config;
use Amp\Redis\Redis;
use Amp\Redis\RemoteExecutor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\Transport;
use function ServiceBus\Common\jsonEncode;

/**
 *
 */
final class RedisPublisher
{
    /** @var Redis|null */
    private $publishClient = null;

    /** @var RedisTransportConnectionConfiguration */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(RedisTransportConnectionConfiguration $config, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Close connection.
     */
    public function disconnect(): void
    {
        if ($this->publishClient !== null)
        {
            $this->publishClient = null;
        }
    }

    /**
     * Send message to Redis server.
     */
    public function publish(OutboundPackage $outboundPackage): Promise
    {
        return call(
            function () use ($outboundPackage): \Generator
            {
                if ($this->publishClient === null)
                {
                    $this->publishClient = new Redis(
                        new RemoteExecutor(Config::fromUri($this->config->toString()))
                    );
                }

                $internalHeaders = [Transport::SERVICE_BUS_TRACE_HEADER => $outboundPackage->traceId];

                /** @var RedisTransportLevelDestination $destination */
                $destination        = $outboundPackage->destination;
                $destinationChannel = $destination->channel;
                $headers            = \array_filter(\array_merge($internalHeaders, $outboundPackage->headers));

                $package = jsonEncode([$outboundPackage->payload, $headers]);

                $this->logger->debug('Publish message to "{channelName}"', [
                    'traceId'     => $outboundPackage->traceId,
                    'channelName' => $destinationChannel,
                    'content'     => $package,
                    'isMandatory' => $outboundPackage->mandatoryFlag,
                ]);

                /** @var int $result */
                $result = yield $this->publishClient->publish($destinationChannel, $package);

                if ($result === 0 && $outboundPackage->mandatoryFlag === true)
                {
                    $this->logger->critical('Publish message failed', [
                        'channelName' => $destinationChannel,
                        'content'     => $package,
                    ]);
                }
            }
        );
    }
}
