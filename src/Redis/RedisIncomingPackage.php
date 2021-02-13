<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Redis;

use function Amp\call;
use Amp\Promise;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 *
 */
final class RedisIncomingPackage implements IncomingPackage
{
    /**
     * Received message id.
     *
     * @var string
     */
    private $id;

    /**
     * Received trace message id.
     *
     * @var string
     */
    private $traceId;

    /**
     * @var string
     */
    private $fromChannel;

    /**
     * @var string
     */
    private $payload;

    /**
     * @psalm-var array<string, string|int|float>
     *
     * @var array
     */
    private $headers;

    /**
     * @psalm-param array<string, string|int|float> $headers
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
