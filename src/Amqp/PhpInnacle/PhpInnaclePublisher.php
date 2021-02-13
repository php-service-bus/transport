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

use function Amp\call;
use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use Psr\Log\LoggerInterface;
use ServiceBus\Transport\Common\Package\OutboundPackage;

/**
 * @internal
 */
final class PhpInnaclePublisher
{
    private const AMQP_DURABLE = 2;

    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Channel $channel, LoggerInterface $logger)
    {
        $this->channel = $channel;
        $this->logger  = $logger;
    }

    /**
     * Send multiple messages to broker (in transaction).
     *
     * @param OutboundPackage ...$outboundPackages
     *
     * @throws \Throwable
     */
    public function processBulk(OutboundPackage ...$outboundPackages): Promise
    {
        return call(
            function () use ($outboundPackages): \Generator
            {
                yield $this->channel->txSelect();

                try
                {
                    $promises = [];

                    foreach ($outboundPackages as $outboundPackage)
                    {
                        $promises[] = $this->process($outboundPackage);
                    }

                    yield $promises;
                    yield $this->channel->txCommit();
                }
                catch (\Throwable $throwable)
                {
                    yield $this->channel->txRollback();

                    throw $throwable;
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
                $internalHeaders = [
                    'delivery-mode' => $outboundPackage->persistentFlag === true ? self::AMQP_DURABLE : null,
                    'expiration'    => $outboundPackage->expiredAfter,
                ];

                /** @var \ServiceBus\Transport\Amqp\AmqpTransportLevelDestination $destination */
                $destination = $outboundPackage->destination;
                $headers     = \array_filter(\array_merge($internalHeaders, $outboundPackage->headers));
                $content     = $outboundPackage->payload;

                $this->logger->debug(
                    'Publish message to "{rabbitMqExchange}" with routing key "{rabbitMqRoutingKey}"',
                    [
                        'traceId'            => $outboundPackage->traceId,
                        'rabbitMqExchange'   => $destination->exchange,
                        'rabbitMqRoutingKey' => $destination->routingKey,
                        'content'            => $content,
                        'headers'            => $headers,
                        'isMandatory'        => $outboundPackage->mandatoryFlag,
                        'isImmediate'        => $outboundPackage->immediateFlag,
                        'expiredAt'          => $outboundPackage->expiredAfter,
                    ]
                );

                yield $this->channel->publish(
                    body: $content,
                    exchange: $destination->exchange,
                    routingKey: (string) $destination->routingKey,
                    headers: \array_filter($headers),
                    mandatory: $outboundPackage->mandatoryFlag,
                    immediate: $outboundPackage->immediateFlag
                );
            }
        );
    }
}
