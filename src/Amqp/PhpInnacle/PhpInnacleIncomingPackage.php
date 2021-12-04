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

use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use PHPinnacle\Ridge\Message;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Exceptions\AcknowledgeFailed;
use ServiceBus\Transport\Common\Exceptions\NotAcknowledgeFailed;
use ServiceBus\Transport\Common\Exceptions\RejectFailed;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function Amp\call;
use function ServiceBus\Common\uuid;

final class PhpInnacleIncomingPackage implements IncomingPackage
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
     * @var Message
     */
    private $originMessage;

    /**
     * @psalm-var array<non-empty-string, int|float|string|null>
     *
     * @var array
     */
    private $headers;

    /**
     * @var Channel
     */
    private $channel;

    public function __construct(Message $message, Channel $channel)
    {
        $this->originMessage = $message;
        $this->channel       = $channel;
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->headers       = $message->headers; /* @phpstan-ignore-line */
        $this->id            = $this->extractFromHeaders(IncomingPackage::HEADER_MESSAGE_ID, uuid());
        $this->traceId       = $this->extractFromHeaders(IncomingPackage::HEADER_TRACE_ID, uuid());
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
        return new AmqpTransportLevelDestination(
            $this->originMessage->exchange,
            $this->originMessage->routingKey
        );
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function payload(): string
    {
        /* @phpstan-ignore-next-line */
        return $this->originMessage->content;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function ack(): Promise
    {
        return call(
            function (): \Generator
            {
                try
                {
                    yield $this->channel->ack($this->originMessage);
                }
                catch (\Throwable $throwable)
                {
                    throw new AcknowledgeFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            }
        );
    }

    public function nack(bool $requeue, ?string $withReason = null): Promise
    {
        return call(
            function () use ($requeue): \Generator
            {
                try
                {
                    yield $this->channel->nack($this->originMessage, false, $requeue);
                }
                catch (\Throwable $throwable)
                {
                    throw new NotAcknowledgeFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            }
        );
    }

    public function reject(bool $requeue, ?string $withReason = null): Promise
    {
        return call(
            function () use ($requeue): \Generator
            {
                try
                {
                    yield $this->channel->reject($this->originMessage, $requeue);
                }
                catch (\Throwable $throwable)
                {
                    throw new RejectFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            }
        );
    }

    /**
     * @psalm-param non-empty-string $key
     * @psalm-param non-empty-string $withDefault
     *
     * @psalm-return non-empty-string
     */
    private function extractFromHeaders(string $key, string $withDefault): string
    {
        $value = (string) $this->headers[$key];

        unset($this->headers[$key]);

        if (!empty($value))
        {
            return $value;
        }

        return $withDefault;
    }
}
