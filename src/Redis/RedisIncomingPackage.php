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
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function Amp\call;

final class RedisIncomingPackage implements IncomingPackage
{
    /**
     * Received message id.
     *
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $id;

    /**
     * Received trace message id.
     *
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $traceId;

    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $fromChannel;

    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $payload;

    /**
     * @psalm-var array<non-empty-string, int|float|string|null>
     *
     * @var array
     */
    private $headers;

    /**
     * @psalm-param non-empty-string $messageId
     * @psalm-param non-empty-string $traceId
     * @psalm-param non-empty-string $payload
     * @psalm-param array<non-empty-string, int|float|string|null> $headers
     * @psalm-param non-empty-string $fromChannel
     */
    public function __construct(string $messageId, string $traceId, string $payload, array $headers, string $fromChannel)
    {
        $this->id          = $messageId;
        $this->traceId     = $traceId;
        $this->payload     = $payload;
        $this->headers     = $headers;
        $this->fromChannel = $fromChannel;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function origin(): DeliveryDestination
    {
        return new RedisTransportLevelDestination($this->fromChannel);
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @codeCoverageIgnore
     */
    public function ack(): Promise
    {
        return call(
            static function ()
            {
            }
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function nack(bool $requeue, ?string $withReason = null): Promise
    {
        return call(
            static function ()
            {
            }
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function reject(bool $requeue, ?string $withReason = null): Promise
    {
        return call(
            static function ()
            {
            }
        );
    }
}
