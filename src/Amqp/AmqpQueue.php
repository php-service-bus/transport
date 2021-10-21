<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Amqp;

use ServiceBus\Transport\Amqp\Exceptions\InvalidQueueName;
use ServiceBus\Transport\Common\Queue;

/**
 * Queue details.
 *
 * @psalm-readonly
 */
final class AmqpQueue implements Queue
{
    private const AMQP_DURABLE = 2;

    private const AMQP_PASSIVE = 4;

    private const AMQP_EXCLUSIVE = 8;

    private const AMQP_AUTO_DELETE = 16;

    private const MAX_NAME_SYMBOLS = 255;

    /**
     * The queue name MAY be empty, in which case the server MUST create a new queue with a unique generated name and
     * return this to the client in the Declare-Ok method. Queue names starting with "amq." are reserved for
     * pre-declared and standardised queues. The client MAY declare a queue starting with "amq." if the passive option
     * is set, or the queue already exists. Error code: access-refused The queue name can be empty, or a sequence of
     * these characters: letters, digits, hyphen, underscore, period, or colon.
     *
     * @psalm-readonly
     *
     * @var string
     */
    public $name;

    /**
     * If set, the server will reply with Declare-Ok if the queue already exists with the same name, and raise an
     * error if not. The client can use this to check whether a queue exists without modifying the server state. When
     * set, all other method fields except name and no-wait are ignored. A declare with both passive and no-wait has
     * no effect. Arguments are compared for semantic equivalence.
     *
     * The client MAY ask the server to assert that a queue exists without creating the queue if not. If the queue does
     * not exist, the server treats this as a failure.
     *
     * If not set and the queue exists, the server MUST check that the existing queue has the same values for durable,
     * exclusive, auto-delete, and arguments fields. The server MUST respond with Declare-Ok if the requested queue
     * matches these fields, and MUST raise a channel exception if not.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $passive;

    /**
     * If set when creating a new queue, the queue will be marked as durable. Durable queues remain active when a
     * server restarts. Non-durable queues (transient queues) are purged if/when a server restarts. Note that durable
     * queues do not necessarily hold persistent messages, although it does not make sense to send persistent messages
     * to a transient queue.
     *
     * The server MUST recreate the durable queue after a restart.
     * The server MUST support both durable and transient queues.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $durable;

    /**
     * Exclusive queues may only be accessed by the current connection, and are deleted when that connection closes.
     * Passive declaration of an exclusive queue by other connections are not allowed.
     *
     * The server MUST support both exclusive (private) and non-exclusive (shared) queues.
     * The client MAY NOT attempt to use a queue that was declared as exclusive by another still-open connection. Error
     * code.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $exclusive;

    /**
     * If set, the queue is deleted when all consumers have finished using it. The last consumer can be cancelled
     * either explicitly or because its channel is closed. If there was no consumer ever on the queue, it won't be
     * deleted. Applications can explicitly delete auto-delete queues using the Delete method as normal.
     *
     * The server MUST ignore the auto-delete field if the queue already exists.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $autoDelete;

    /**
     * @see http://www.rabbitmq.com/amqp-0-9-1-reference.html#domain.table
     *
     * @psalm-readonly
     *
     * @var array
     */
    public $arguments;

    /**
     * Queue flags.
     *
     * @psalm-readonly
     *
     * @var int
     */
    public $flags;

    /**
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidQueueName
     */
    public static function default(string $name): self
    {
        return new self($name);
    }

    /**
     * Create quorum queue.
     *
     * @see https://www.rabbitmq.com/quorum-queues.html
     *
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidQueueName
     */
    public static function quorum(string $name): self
    {
        return new self(
            name: $name,
            durable: true,
            exclusive: false,
            arguments: ['x-queue-type' => 'quorum']
        );
    }

    /**
     * Create delayed queue.
     *
     * @see https://github.com/rabbitmq/rabbitmq-delayed-message-exchange
     *
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidQueueName
     */
    public static function delayed(string $name, AmqpExchange $toExchange): self
    {
        return new self(
            name: $name,
            passive: true,
            arguments: ['x-dead-letter-exchange' => $toExchange->name]
        );
    }

    public function makePassive(): self
    {
        return new self(
            name: $this->name,
            passive: true,
            durable: $this->durable,
            exclusive: $this->exclusive,
            autoDelete: $this->autoDelete,
            arguments: $this->arguments,
            flags: $this->passive ? $this->flags : $this->flags + self::AMQP_PASSIVE
        );
    }

    public function makeExclusive(): self
    {
        return new self(
            name: $this->name,
            passive: $this->passive,
            durable: $this->durable,
            exclusive: true,
            autoDelete: $this->autoDelete,
            arguments: $this->arguments,
            flags: $this->exclusive ? $this->flags : $this->flags + self::AMQP_EXCLUSIVE
        );
    }

    public function makeDurable(): self
    {
        return new self(
            name: $this->name,
            passive: $this->passive,
            durable: true,
            exclusive: $this->exclusive,
            autoDelete: $this->autoDelete,
            arguments: $this->arguments,
            flags: $this->durable ? $this->flags : $this->flags + self::AMQP_DURABLE
        );
    }

    public function enableAutoDelete(): self
    {
        return new self(
            name: $this->name,
            passive: $this->passive,
            durable: $this->durable,
            exclusive: $this->exclusive,
            autoDelete: true,
            arguments: $this->arguments,
            flags: $this->autoDelete ? $this->flags : $this->flags + self::AMQP_AUTO_DELETE
        );
    }

    public function wthArguments(array $arguments): self
    {
        return new self(
            name: $this->name,
            passive: $this->passive,
            durable: $this->durable,
            exclusive: $this->exclusive,
            autoDelete: $this->autoDelete,
            arguments: \array_merge($this->arguments, $arguments),
            flags: $this->flags
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return $this->name;
    }

    private function __construct(
        string $name,
        bool $passive = false,
        bool $durable = false,
        bool $exclusive = false,
        bool $autoDelete = false,
        array $arguments = [],
        int $flags = 0
    ) {
        if ($name === '')
        {
            throw InvalidQueueName::nameCantBeEmpty();
        }

        if (self::MAX_NAME_SYMBOLS < \mb_strlen($name))
        {
            throw InvalidQueueName::nameIsToLong($name);
        }

        $this->name       = $name;
        $this->passive    = $passive;
        $this->durable    = $durable;
        $this->exclusive  = $exclusive;
        $this->autoDelete = $autoDelete;
        $this->arguments  = $arguments;
        $this->flags      = $flags;
    }
}
