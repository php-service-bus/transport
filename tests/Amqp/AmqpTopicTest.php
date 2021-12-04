<?php

/** @noinspection PhpUnhandledExceptionInspection */

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Transport\Tests\Amqp;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Amqp\AmqpExchange;
use ServiceBus\Transport\Amqp\Exceptions\InvalidExchangeName;

/**
 *
 */
final class AmqpTopicTest extends TestCase
{
    /**
     * @test
     */
    public function createWithToLongName(): void
    {
        $this->expectException(InvalidExchangeName::class);
        $this->expectExceptionMessage('Exchange name may be up to 255 bytes of UTF-8 characters (260 specified)');

        AmqpExchange::fanout(\str_repeat('x', 260));
    }

    /**
     * @test
     */
    public function fanoutCreate(): void
    {
        $exchange = AmqpExchange::fanout('fanoutName');

        self::assertSame('fanout', $exchange->type->value);
        self::assertSame('fanoutName', $exchange->toString());
    }

    /**
     * @test
     */
    public function directCreate(): void
    {
        $exchange = AmqpExchange::direct('directName');

        self::assertSame('direct', $exchange->type->value);
        self::assertSame('directName', $exchange->toString());
    }

    /**
     * @test
     */
    public function topicCreate(): void
    {
        $exchange = AmqpExchange::topic('topicName');

        self::assertSame('topic', $exchange->type->value);
        self::assertSame('topicName', $exchange->toString());
    }

    /**
     * @test
     */
    public function delayedCreate(): void
    {
        $exchange = AmqpExchange::delayed('delayedName');

        self::assertSame('x-delayed-message', $exchange->type->value);
        self::assertSame('delayedName', $exchange->toString());

        self::assertSame(['x-delayed-type' => 'direct'], $exchange->arguments);
    }

    /**
     * @test
     */
    public function flags(): void
    {
        $exchange = AmqpExchange::direct('directName')->makeDurable();

        /** @see AmqpExchange::AMQP_DURABLE */
        self::assertSame(2, $exchange->flags);

        /** @see AmqpExchange::AMQP_PASSIVE */
        $exchange = $exchange->makePassive();
        self::assertSame(6, $exchange->flags);

        $exchange = $exchange->wthArguments(['key' => 'value']);
        self::assertSame(['key' => 'value'], $exchange->arguments);
    }
}
