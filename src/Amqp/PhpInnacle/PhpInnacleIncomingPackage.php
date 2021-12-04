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
        /* @phpstan-ignore-next-line */
        $this->headers = $message->headers;
        $this->id      = $this->extractUuidFromHeaders(IncomingPackage::HEADER_MESSAGE_ID);
        $this->traceId = $this->extractUuidFromHeaders(IncomingPackage::HEADER_TRACE_ID);
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
     *
     * @psalm-return non-empty-string
     */
    private function extractUuidFromHeaders(string $key): string
    {
        if (\array_key_exists($key, $this->headers) && \is_string($this->headers[$key]) && $this->headers[$key] !== '')
        {
            $value = $this->headers[$key];

            unset($this->headers[$key]);

            return $value;
        }

        return uuid();
    }
}
