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
use Nsq\Producer;
use ServiceBus\Transport\Common\Exceptions\ConnectionFail;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use function Amp\call;
use function ServiceBus\Common\jsonEncode;

final class NsqPublisher
{
    /**
     * @var Producer|null
     */
    private $publishClient;

    /**
     * @var NsqTransportConnectionConfiguration
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(NsqTransportConnectionConfiguration $config, ?LoggerInterface $logger = null)
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
            /** @psalm-suppress InternalMethod */
            $this->publishClient->close();
            $this->publishClient = null;
        }
    }

    /**
     * Send multiple messages to Nsq server.
     *
     * @psalm-return Promise<void>
     */
    public function publishBulk(OutboundPackage ...$outboundPackages): Promise
    {
        return call(
            function () use ($outboundPackages): \Generator
            {
                /** @todo: fix me Support transactions? */

                $promises = [];

                foreach ($outboundPackages as $outboundPackage)
                {
                    $promises[] = $this->publish($outboundPackage);
                }

                yield $promises;
            }
        );
    }

    /**
     * Send message to Nsq server.
     */
    public function publish(OutboundPackage $outboundPackage): Promise
    {
        return call(
            function () use ($outboundPackage): \Generator
            {
                if ($this->publishClient === null)
                {
                    $this->publishClient = new Producer(
                        $this->config->toString(),
                        new ClientConfig(),
                        $this->logger,
                    );
                }

                /** @var NsqTransportLevelDestination $destination */
                $destination        = $outboundPackage->destination;
                $destinationChannel = $destination->channel;
                $headers            = \array_merge($outboundPackage->headers, [
                    IncomingPackage::HEADER_TRACE_ID => $outboundPackage->traceId
                ]);

                $package = jsonEncode([$outboundPackage->payload, $headers]);

                $this->logger->debug('Publish message to "{channelName}"', [
                    'traceId'     => $outboundPackage->traceId,
                    'channelName' => $destinationChannel,
                    'content'     => $package,
                    'isMandatory' => $outboundPackage->mandatoryFlag,
                ]);

                try
                {
                    yield $this->publishClient->connect();
                }
                catch (ConnectException $e)
                {
                    throw ConnectionFail::fromThrowable($e);
                }

                $result = yield $this->publishClient->publish($destinationChannel, $package);

                if ($result === false && $outboundPackage->mandatoryFlag === true)
                {
                    $this->logger->critical('Publish message failed', [
                        'traceId'     => $outboundPackage->traceId,
                        'channelName' => $destinationChannel,
                        'content'     => $package,
                    ]);
                }
            }
        );
    }
}
