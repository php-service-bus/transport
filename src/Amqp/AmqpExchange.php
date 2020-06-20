<?php

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Amqp;

use ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName;
use ServiceBus\Transport\Common\Topic;

/**
 * Exchange details.
 *
 * @psalm-readonly
 */
final class AmqpExchange implements Topic
{
    private const TYPE_FANOUT = 'fanout';

    private const TYPE_DIRECT = 'direct';

    private const TYPE_TOPIC = 'topic';

    private const AMQP_DURABLE = 2;

    private const AMQP_PASSIVE = 4;

    /** Plugin rabbitmq_delayed_message_exchange */
    private const TYPE_DELAYED = 'x-delayed-message';

    private const MAX_NAME_SYMBOLS = 255;

    /**
     * The exchange name consists of a non-empty sequence of these characters: letters, digits, hyphen, underscore,
     * period, or colon.
     *
     * @var string
     */
    public $name;

    /**
     * Exchange type.
     *
     * - fanout
     * - direct
     * - topic
     * - x-delayed-message
     *
     * @var string
     */
    public $type;

    /**
     *  If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an
     *  error if not. The client can use this to check whether an exchange exists without modifying the server state.
     *  When set, all other method fields except name and no-wait are ignored. A declare with both passive and no-wait
     *  has no effect. Arguments are compared for semantic equivalence.
     *
     * If set, and the exchange does not already exist, the server MUST raise a channel exception with reply code 404
     * (not found). If not set and the exchange exists, the server MUST check that the existing exchange has the same
     * values for type, durable, and arguments fields. The server MUST respond with Declare-Ok if the requested
     * exchange matches these fields, and MUST raise a channel exception if not.
     *
     * @var bool
     */
    public $passive = false;

    /**
     * If set when creating a new exchange, the exchange will be marked as durable. Durable exchanges remain active
     * when a server restarts. Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
     *
     * @var bool
     */
    public $durable = false;

    /**
     * @see       https://www.rabbitmq.com/amqp-0-9-1-reference.html#domain.table
     *
     * @psalm-var array<array-key, string|int|float>
     *
     * @var array
     */
    public $arguments = [];

    /**
     * Exchange flags.
     *
     * @var int
     */
    public $flags = 0;

    /**
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function fanout(string $name, bool $durable = false): self
    {
        return new self($name, self::TYPE_FANOUT, $durable);
    }

    /**
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function direct(string $name, bool $durable = false): self
    {
        return new self($name, self::TYPE_DIRECT, $durable);
    }

    /**
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function topic(string $name, bool $durable = false): self
    {
        return new self($name, self::TYPE_TOPIC, $durable);
    }

    /**
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function delayed(string $name): self
    {
        return new self($name, self::TYPE_DELAYED, true, ['x-delayed-type' => self::TYPE_DIRECT]);
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return $this->name;
    }

    public function makePassive(): self
    {
        if ($this->passive === false)
        {
            $this->passive = true;
            $this->flags   += self::AMQP_PASSIVE;
        }

        return $this;
    }

    public function makeDurable(): self
    {
        if ($this->durable === false)
        {
            $this->durable = true;
            $this->flags   += self::AMQP_DURABLE;
        }

        return $this;
    }

    public function wthArguments(array $arguments): self
    {
        /** @psalm-suppress MixedTypeCoercion */
        $this->arguments = \array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * @psalm-param array<array-key, string|int|float> $arguments
     *
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    private function __construct(string $name, string $type, bool $durable, array $arguments = [])
    {
        if ($name === '')
        {
            throw InvalidExchangeName::nameCantBeEmpty();
        }

        if (self::MAX_NAME_SYMBOLS < \mb_strlen($name))
        {
            throw InvalidExchangeName::nameIsToLong($name);
        }

        $this->arguments = $arguments;
        $this->name      = $name;
        $this->type      = $type;

        if ($durable === true)
        {
            $this->makeDurable();
        }
    }
}
