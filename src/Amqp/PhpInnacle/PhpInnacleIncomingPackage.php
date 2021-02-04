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
use function ServiceBus\Common\uuid;
use Amp\Promise;
use PHPinnacle\Ridge\Channel;
use PHPinnacle\Ridge\Message;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Exceptions\AcknowledgeFailed;
use ServiceBus\Transport\Common\Exceptions\NotAcknowledgeFailed;
use ServiceBus\Transport\Common\Exceptions\RejectFailed;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 *
 */
final class PhpInnacleIncomingPackage implements IncomingPackage
{
    /**
     * Received package id.
     *
     * @var string|null
     */
    private $id;

    /**
     * @var Message
     */
    private $originMessage;

    /**
     * @var Channel
     */
    private $channel;

    public function __construct(Message $message, Channel $channel)
    {
        $this->originMessage = $message;
        $this->channel       = $channel;
    }

    public function id(): string
    {
        if ($this->id === null)
        {
            $this->id = uuid();
        }

        return $this->id;
    }

    public function origin(): DeliveryDestination
    {
        return new AmqpTransportLevelDestination(
            $this->originMessage->exchange,
            $this->originMessage->routingKey
        );
    }

    public function payload(): string
    {
        return $this->originMessage->content;
    }

    public function headers(): array
    {
        /**
         * @noinspection PhpUnnecessaryLocalVariableInspection
         * @noinspection OneTimeUseVariablesInspection
         *
         * @psalm-var    array<string, string|int|float> $headers
         */
        $headers = $this->originMessage->headers;

        return $headers;
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
}
