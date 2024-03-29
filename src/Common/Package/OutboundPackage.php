<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Common\Package;

use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Outbound package.
 *
 * @psalm-immutable
 */
class OutboundPackage
{
    /**
     * Message trace id.
     *
     * @psalm-readonly
     *
     * @var string
     */
    public $traceId;

    /**
     * Message body.
     *
     * @psalm-readonly
     *
     * @var string
     */
    public $payload;

    /**
     * Message headers.
     *
     * @psalm-readonly
     * @psalm-var array<string, int|float|string|null>
     *
     * @var array
     */
    public $headers;

    /**
     * Message destination.
     *
     * @psalm-readonly
     *
     * @var DeliveryDestination
     */
    public $destination;

    /**
     * The message must be stored in the broker.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $persistentFlag;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue. If this flag is set, the
     * server will return an unroutable message with a Return method. If this flag is zero, the server silently drops
     * the message.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $mandatoryFlag;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue consumer immediately. If this
     * flag is set, the server will return an undeliverable message with a Return method. If this flag is zero, the
     * server will queue the message, but with no guarantee that it will ever be consumed.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $immediateFlag;

    /**
     * The message will be marked expired after N milliseconds.
     *
     * @psalm-readonly
     *
     * @var int|null
     */
    public $expiredAfter;

    /**
     * @psalm-param array<string, int|float|string|null> $headers
     */
    public function __construct(
        string $traceId,
        string $payload,
        array $headers,
        DeliveryDestination $destination,
        bool $persist = false,
        bool $mandatory = false,
        bool $immediate = false,
        ?int $expiredAfter = null
    ) {
        $this->traceId        = $traceId;
        $this->payload        = $payload;
        $this->headers        = $headers;
        $this->destination    = $destination;
        $this->persistentFlag = $persist;
        $this->mandatoryFlag  = $mandatory;
        $this->immediateFlag  = $immediate;
        $this->expiredAfter   = $expiredAfter;
    }
}
