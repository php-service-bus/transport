<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Nsq;

use Generator;
use Nsq\Message;
use function Amp\call;
use Amp\Promise;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 *
 */
final class NsqIncomingPackage implements IncomingPackage
{
    /**
     * @var Message
     */
    private $message;

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
    public function __construct(Message $message, string $messageId, string $traceId, string $payload, array $headers, string $fromChannel)
    {
        $this->message     = $message;
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
        return new NsqTransportLevelDestination($this->fromChannel);
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
            function (): Generator
            {
                yield $this->message->finish();
            }
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function nack(bool $requeue, ?string $withReason = null): Promise
    {
        return call(
            function () use ($requeue): Generator
            {
                if ($requeue)
                {
                    yield $this->message->requeue(10);
                }
                else
                {
                    yield $this->message->finish();
                }
            }
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function reject(bool $requeue, ?string $withReason = null): Promise
    {
        return call(
            function () use ($requeue): Generator
            {
                if ($requeue)
                {
                    yield $this->message->requeue(10);
                }
                else
                {
                    yield $this->message->finish();
                }
            }
        );
    }
}
