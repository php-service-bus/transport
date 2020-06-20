<?php

/**
 * Common transport implementation interfaces.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Common\Package;

use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Outbound package.
 *
 * @psalm-readonly
 */
class OutboundPackage
{
    /**
     * Message body.
     *
     * @var string
     */
    public $payload;

    /**
     * Message headers.
     *
     * @psalm-var array<string, float|int|string>
     *
     * @var array
     */
    public $headers;

    /**
     * Message destination.
     *
     * @var DeliveryDestination
     */
    public $destination;

    /**
     * The message must be stored in the broker.
     *
     * @var bool
     */
    public $persistentFlag = false;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue. If this flag is set, the
     * server will return an unroutable message with a Return method. If this flag is zero, the server silently drops
     * the message.
     *
     * @var bool
     */
    public $mandatoryFlag = false;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue consumer immediately. If this
     * flag is set, the server will return an undeliverable message with a Return method. If this flag is zero, the
     * server will queue the message, but with no guarantee that it will ever be consumed.
     *
     * @var bool
     */
    public $immediateFlag = false;

    /**
     * The message will be marked expired after N milliseconds.
     *
     * @var int|null
     */
    public $expiredAfter = null;

    /**
     * Trace operation id.
     *
     * @var int|string|null
     */
    public $traceId;

    /**
     * @psalm-param array<string, float|int|string> $headers
     *
     * @param int|string|null $traceId
     */
    public function __construct(
        string $payload,
        array $headers,
        DeliveryDestination $destination,
        $traceId,
        bool $persist = false,
        bool $mandatory = false,
        bool $immediate = false,
        ?int $expiredAfter = null
    ) {
        $this->payload        = $payload;
        $this->headers        = $headers;
        $this->destination    = $destination;
        $this->traceId        = $traceId;
        $this->persistentFlag = $persist;
        $this->mandatoryFlag  = $mandatory;
        $this->immediateFlag  = $immediate;
        $this->expiredAfter   = $expiredAfter;
    }
}
