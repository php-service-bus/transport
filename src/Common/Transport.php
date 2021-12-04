<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Common;

use Amp\Promise;
use ServiceBus\Transport\Common\Package\OutboundPackage;

/**
 * Messages transport interface.
 */
interface Transport
{
    /**
     * Create topic and bind them
     * If the topic to which we binds does not exist, it will be created.
     *
     * @psalm-return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail Connection refused
     * @throws \ServiceBus\Transport\Common\Exceptions\CreateTopicFailed Failed to create topic
     * @throws \ServiceBus\Transport\Common\Exceptions\BindFailed Failed topic bind
     */
    public function createTopic(Topic $topic, TopicBind ...$binds): Promise;

    /**
     * Create queue and bind to topic(s)
     * If the topic to which we binds does not exist, it will be created.
     *
     * @psalm-return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail Connection refused
     * @throws \ServiceBus\Transport\Common\Exceptions\CreateQueueFailed Failed to create queue
     * @throws \ServiceBus\Transport\Common\Exceptions\BindFailed Failed queue bind
     */
    public function createQueue(Queue $queue, QueueBind ...$binds): Promise;

    /**
     * Consume to queue.
     *
     * @psalm-param callable(\ServiceBus\Transport\Common\Package\IncomingPackage):\Generator $onMessage
     *
     * @psalm-return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail Connection refused
     */
    public function consume(callable $onMessage, Queue ... $queues): Promise;

    /**
     * Stop subscription.
     *
     * @psalm-return Promise<void>
     */
    public function stop(): Promise;

    /**
     * Send message to broker.
     *
     * @psalm-return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\SendMessageFailed Failed to send message
     */
    public function send(OutboundPackage ...$outboundPackages): Promise;

    /**
     * Connect to broker.
     *
     * @psalm-return Promise<void>
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail Connection refused
     */
    public function connect(): Promise;

    /**
     * Close connection.
     *
     * @psalm-return Promise<void>
     */
    public function disconnect(): Promise;
}
