<?php

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

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
     *
     * @throws \Throwable
     */
    public function createWithEmptyName(): void
    {
        $this->expectException(InvalidExchangeName::class);
        $this->expectExceptionMessage('Exchange name must be specified');

        AmqpExchange::fanout('');
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function createWithToLongName(): void
    {
        $this->expectException(InvalidExchangeName::class);
        $this->expectExceptionMessage('Exchange name may be up to 255 bytes of UTF-8 characters (260 specified)');

        AmqpExchange::fanout(\str_repeat('x', 260));
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function fanoutCreate(): void
    {
        $exchange = AmqpExchange::fanout('fanoutName');

        self::assertSame('fanout', $exchange->type);
        self::assertSame('fanoutName', $exchange->toString());
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function directCreate(): void
    {
        $exchange = AmqpExchange::direct('directName');

        self::assertSame('direct', $exchange->type);
        self::assertSame('directName', $exchange->toString());
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function topicCreate(): void
    {
        $exchange = AmqpExchange::topic('topicName');

        self::assertSame('topic', $exchange->type);
        self::assertSame('topicName', $exchange->toString());
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function delayedCreate(): void
    {
        $exchange = AmqpExchange::delayed('delayedName');

        self::assertSame('x-delayed-message', $exchange->type);
        self::assertSame('delayedName', $exchange->toString());

        self::assertSame(['x-delayed-type' => 'direct'], $exchange->arguments);
    }

    /**
     * @test
     *
     * @throws \Throwable
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
