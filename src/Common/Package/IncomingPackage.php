<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Common\Package;

use Amp\Promise;
use ServiceBus\Transport\Common\DeliveryDestination;

/**
 * Incoming package.
 */
interface IncomingPackage
{
    /**
     * Receive package id.
     */
    public function id(): string;

    /**
     * The source from which the message was received.
     */
    public function origin(): DeliveryDestination;

    /**
     * Receive message body.
     */
    public function payload(): string;

    /**
     * Receive message headers bag.
     *
     * @psalm-return array<string, string|int|float>
     */
    public function headers(): array;

    /**
     * Acks given message.
     *
     * @return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\AcknowledgeFailed
     */
    public function ack(): Promise;

    /**
     * Nacks message.
     *
     * @param bool        $requeue    Send back to the queue
     * @param string|null $withReason Reason for refusal
     *
     * @return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\NotAcknowledgeFailed
     */
    public function nack(bool $requeue, ?string $withReason = null): Promise;

    /**
     * Rejects message.
     *
     * @param bool        $requeue    Send back to the queue
     * @param string|null $withReason Reason for refusal
     *
     * @return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\RejectFailed
     */
    public function reject(bool $requeue, ?string $withReason = null): Promise;

    /**
     * Receive trace id.
     *
     * @return int|string
     */
    public function traceId(): int|string;
}
