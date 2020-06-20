<?php

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Amqp;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Amqp\AmqpExchange;
use ServiceBus\Transport\Amqp\AmqpQueue;
use ServiceBus\Transport\Amqp\Exceptions\InvalidQueueName;

/**
 *
 */
final class AmqpQueueTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     */
    public function createWithEmptyName(): void
    {
        $this->expectException(InvalidQueueName::class);
        $this->expectExceptionMessage('Queue name must be specified');

        AmqpQueue::default('');
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function createWithToLongName(): void
    {
        $this->expectException(InvalidQueueName::class);
        $this->expectExceptionMessage('Queue name may be up to 255 bytes of UTF-8 characters (260 specified)');

        AmqpQueue::default(\str_repeat('x', 260));
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function defaultCreate(): void
    {
        $queue = AmqpQueue::default(__METHOD__);

        static::assertSame(__METHOD__, $queue->toString());

        static::assertSame(0, $queue->flags);
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function delayedCreate(): void
    {
        $queue = AmqpQueue::delayed('test', AmqpExchange::direct('qwerty'));

        static::assertSame('test', $queue->toString());

        /** @see AmqpQueue::AMQP_DURABLE */
        static::assertSame(2, $queue->flags);
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function flags(): void
    {
        $queue = AmqpQueue::default(__METHOD__, true);

        /** @see AmqpQueue::AMQP_DURABLE */
        static::assertSame(2, $queue->flags);

        /** @see AmqpQueue::AMQP_PASSIVE */
        $queue->makePassive();
        static::assertSame(6, $queue->flags);

        /** @see AmqpQueue::AMQP_AUTO_DELETE */
        $queue->enableAutoDelete();
        static::assertSame(22, $queue->flags);

        /** @see AmqpQueue::AMQP_EXCLUSIVE */
        $queue->makeExclusive();
        static::assertSame(30, $queue->flags);

        $queue->wthArguments(['key' => 'value']);
        static::assertSame(['key' => 'value'], $queue->arguments);
    }
}
