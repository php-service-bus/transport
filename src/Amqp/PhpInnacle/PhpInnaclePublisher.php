<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Amqp\PhpInnacle;

use function Amp\call;
use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use Psr\Log\LoggerInterface;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\Transport;

/**
 * @internal
 */
final class PhpInnaclePublisher
{
    private const AMQP_DURABLE = 2;

    /** @var Channel */
    private $channel;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Channel $channel, LoggerInterface $logger)
    {
        $this->channel = $channel;
        $this->logger  = $logger;
    }

    /**
     * Send message to broker.
     */
    public function process(OutboundPackage $outboundPackage): Promise
    {
        return call(
            function () use ($outboundPackage): \Generator
            {
                $channel = $this->channel;

                $internalHeaders = [
                    'delivery-mode'                     => $outboundPackage->persistentFlag === true ? self::AMQP_DURABLE : null,
                    'expiration'                        => $outboundPackage->expiredAfter,
                    Transport::SERVICE_BUS_TRACE_HEADER => $outboundPackage->traceId,
                ];

                /** @var \ServiceBus\Transport\Amqp\AmqpTransportLevelDestination $destination */
                $destination = $outboundPackage->destination;
                $headers     = \array_filter(\array_merge($internalHeaders, $outboundPackage->headers));
                $content     = $outboundPackage->payload;

                $this->logger->debug('Publish message to "{rabbitMqExchange}" with routing key "{rabbitMqRoutingKey}"', [
                    'traceId'            => $outboundPackage->traceId,
                    'rabbitMqExchange'   => $destination->exchange,
                    'rabbitMqRoutingKey' => $destination->routingKey,
                    'content'            => $content,
                    'headers'            => $headers,
                    'isMandatory'        => $outboundPackage->mandatoryFlag,
                    'isImmediate'        => $outboundPackage->immediateFlag,
                    'expiredAt'          => $outboundPackage->expiredAfter,
                ]);

                yield $channel->publish(
                    $content,
                    $destination->exchange,
                    (string) $destination->routingKey,
                    \array_filter($headers),
                    $outboundPackage->mandatoryFlag,
                    $outboundPackage->immediateFlag
                );
            }
        );
    }
}
