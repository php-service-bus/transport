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

use PHPinnacle\Ridge\Client;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use Psr\Log\LoggerInterface;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use function Amp\call;

/**
 * @internal
 */
final class PhpInnaclePublisher
{
    private const AMQP_DURABLE = 2;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Channel|null
     */
    private $regularChannel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Send multiple messages to broker (in transaction).
     *
     * @psalm-return Promise<void>
     *
     * @throws \Throwable
     */
    public function processBulk(OutboundPackage ...$outboundPackages): Promise
    {
        return call(
            function () use ($outboundPackages): \Generator
            {
                /** @var Channel $channel */
                $channel = yield $this->client->channel();

                yield $channel->txSelect();

                try
                {
                    $promises = [];

                    foreach ($outboundPackages as $outboundPackage)
                    {
                        /** @var AmqpTransportLevelDestination $destination */
                        $destination = $outboundPackage->destination;
                        $headers     = $this->prepareHeaders($outboundPackage);

                        $this->logDelivery(
                            package: $outboundPackage,
                            destination: $destination,
                            headers: $headers
                        );

                        $promises[] = $channel->publish(
                            body: $outboundPackage->payload,
                            exchange: $destination->exchange,
                            routingKey: (string) $destination->routingKey,
                            headers: $headers,
                            mandatory: $outboundPackage->mandatoryFlag,
                            immediate: $outboundPackage->immediateFlag
                        );
                    }

                    yield $promises;
                    yield $channel->txCommit();
                }
                catch (\Throwable $throwable)
                {
                    yield $channel->txRollback();

                    throw $throwable;
                }
                finally
                {
                    yield $channel->close();
                }
            }
        );
    }

    /**
     * Send message to broker.
     *
     * @throws \Throwable
     */
    public function process(OutboundPackage $outboundPackage): Promise
    {
        return call(
            function () use ($outboundPackage): \Generator
            {
                if ($this->regularChannel === null)
                {
                    $this->regularChannel = yield $this->client->channel();
                }

                /** @var Channel $channel */
                $channel = $this->regularChannel;

                /** @var AmqpTransportLevelDestination $destination */
                $destination = $outboundPackage->destination;
                $headers     = $this->prepareHeaders($outboundPackage);

                $this->logDelivery(
                    package: $outboundPackage,
                    destination: $destination,
                    headers: $headers
                );

                yield $channel->publish(
                    body: $outboundPackage->payload,
                    exchange: $destination->exchange,
                    routingKey: (string) $destination->routingKey,
                    headers: $headers,
                    mandatory: $outboundPackage->mandatoryFlag,
                    immediate: $outboundPackage->immediateFlag
                );
            }
        );
    }

    private function logDelivery(
        OutboundPackage $package,
        AmqpTransportLevelDestination $destination,
        array $headers
    ): void {
        $this->logger->debug(
            'Publish message to "{rabbitMqExchange}" with routing key "{rabbitMqRoutingKey}"',
            [
                'traceId'            => $package->traceId,
                'rabbitMqExchange'   => $destination->exchange,
                'rabbitMqRoutingKey' => $destination->routingKey,
                'content'            => $package->payload,
                'headers'            => $headers,
                'isMandatory'        => $package->mandatoryFlag,
                'isImmediate'        => $package->immediateFlag,
                'expiredAt'          => $package->expiredAfter,
            ]
        );
    }

    private function prepareHeaders(OutboundPackage $outboundPackage): array
    {
        $internalHeaders = [
            'delivery-mode'                  => $outboundPackage->persistentFlag === true ? self::AMQP_DURABLE : null,
            'expiration'                     => $outboundPackage->expiredAfter,
            IncomingPackage::HEADER_TRACE_ID => $outboundPackage->traceId
        ];

        return \array_filter(\array_merge($internalHeaders, $outboundPackage->headers));
    }
}
