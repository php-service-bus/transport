<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Amqp;

use ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName;
use ServiceBus\Transport\Common\Topic;

/**
 * Exchange details.
 */
final class AmqpExchange implements Topic
{
    private const AMQP_DURABLE = 2;
    private const AMQP_PASSIVE = 4;

    private const MAX_NAME_SYMBOLS = 255;

    /**
     * The exchange name consists of a non-empty sequence of these characters: letters, digits, hyphen, underscore,
     * period, or colon.
     *
     * @psalm-readonly
     * @psalm-var non-empty-string
     *
     * @var string
     */
    public $name;

    /**
     * Exchange type.
     *
     * @psalm-readonly
     *
     * @var AmqpExchangeType
     */
    public $type;

    /**
     * If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an
     * error if not. The client can use this to check whether an exchange exists without modifying the server state.
     * When set, all other method fields except name and no-wait are ignored. A declare with both passive and no-wait
     * has no effect. Arguments are compared for semantic equivalence.
     *
     * If set, and the exchange does not already exist, the server MUST raise a channel exception with reply code 404
     * (not found). If not set and the exchange exists, the server MUST check that the existing exchange has the same
     * values for type, durable, and arguments fields. The server MUST respond with Declare-Ok if the requested
     * exchange matches these fields, and MUST raise a channel exception if not.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $passive;

    /**
     * If set when creating a new exchange, the exchange will be marked as durable. Durable exchanges remain active
     * when a server restarts. Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $durable;

    /**
     * @see https://www.rabbitmq.com/amqp-0-9-1-reference.html#domain.table
     *
     * @psalm-readonly
     *
     * @psalm-var array<array-key, string|int|float>
     *
     * @var array
     */
    public $arguments;

    /**
     * Exchange flags.
     *
     * @psalm-readonly
     *
     * @var int
     */
    public $flags;

    /**
     * @psalm-param non-empty-string $name
     *
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function fanout(string $name): self
    {
        return new self(
            name: $name,
            type: AmqpExchangeType::FANOUT
        );
    }

    /**
     * @psalm-param non-empty-string $name
     *
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function direct(string $name): self
    {
        return new self(
            name: $name,
            type: AmqpExchangeType::DIRECT
        );
    }

    /**
     * @psalm-param non-empty-string $name
     *
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function topic(string $name): self
    {
        return new self(
            name: $name,
            type: AmqpExchangeType::TOPIC
        );
    }

    /**
     * @psalm-param non-empty-string $name
     *
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function delayed(string $name): self
    {
        return new self(
            name: $name,
            type: AmqpExchangeType::DELAYED,
            durable: true,
            arguments: [
                'x-delayed-type' => AmqpExchangeType::DIRECT->value
            ]
        );
    }

    /**
     * @psalm-param non-empty-string $name
     *
     * @throws \ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName
     */
    public static function headers(string $name): self
    {
        return new self(
            name: $name,
            type: AmqpExchangeType::HEADERS
        );
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function makePassive(): self
    {
        return new self(
            name: $this->name,
            type: $this->type,
            passive: true,
            durable: $this->durable,
            arguments: $this->arguments,
            flags: $this->passive ? $this->flags : $this->flags + self::AMQP_PASSIVE
        );
    }

    public function makeDurable(): self
    {
        return new self(
            name: $this->name,
            type: $this->type,
            passive: $this->passive,
            durable: true,
            arguments: $this->arguments,
            flags: $this->durable ? $this->flags : $this->flags + self::AMQP_DURABLE
        );
    }

    public function wthArguments(array $arguments): self
    {
        /** @psalm-var array<array-key, float|int|string> $merged */
        $merged = \array_merge($this->arguments, $arguments);

        return new self(
            name: $this->name,
            type: $this->type,
            passive: $this->passive,
            durable: $this->durable,
            arguments: $merged,
            flags: $this->flags
        );
    }

    /**
     * @psalm-param non-empty-string                   $name
     * @psalm-param array<array-key, string|int|float> $arguments
     */
    private function __construct(
        string           $name,
        AmqpExchangeType $type,
        bool             $passive = false,
        bool             $durable = false,
        array            $arguments = [],
        int              $flags = 0
    ) {
        if (self::MAX_NAME_SYMBOLS < \mb_strlen($name))
        {
            throw InvalidExchangeName::nameIsToLong($name);
        }

        $this->name      = $name;
        $this->type      = $type;
        $this->passive   = $passive;
        $this->durable   = $durable;
        $this->arguments = $arguments;
        $this->flags     = $flags;
    }
}
