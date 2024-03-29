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

use Amp\Promise;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Incoming package.
 */
interface IncomingPackage
{
    public const HEADER_TRACE_ID = 'x-trace-id';
    public const HEADER_MESSAGE_ID   = 'x-message-id';

    /**
     * Receive message id.
     *
     * @psalm-return non-empty-string
     */
    public function id(): string;

    /**
     * Receive message id.
     *
     * @psalm-return non-empty-string
     */
    public function traceId(): string;

    /**
     * The source from which the message was received.
     */
    public function origin(): DeliveryDestination;

    /**
     * Receive message body.
     *
     * @psalm-return non-empty-string
     */
    public function payload(): string;

    /**
     * Receive message headers bag.
     *
     * @psalm-return array<non-empty-string, int|float|string|null>
     */
    public function headers(): array;

    /**
     * Acks given message.
     *
     * @psalm-return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\AcknowledgeFailed
     */
    public function ack(): Promise;

    /**
     * Nacks message.
     *
     * @psalm-param non-empty-string|null $withReason Reason for refusal
     *
     * @psalm-return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\NotAcknowledgeFailed
     */
    public function nack(bool $requeue, ?string $withReason = null): Promise;

    /**
     * Rejects message.
     *
     * @psalm-param non-empty-string|null $withReason Reason for refusal
     *
     * @psalm-return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\RejectFailed
     */
    public function reject(bool $requeue, ?string $withReason = null): Promise;
}
