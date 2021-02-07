<?php /** @noinspection PhpUnhandledExceptionInspection */

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
use ServiceBus\Transport\Amqp\AmqpQueue;
use ServiceBus\Transport\Amqp\Exceptions\InvalidQueueName;

/**
 *
 */
final class AmqpQueueTest extends TestCase
{
    public function createWithEmptyName(): void
    {
        $this->expectException(InvalidQueueName::class);
        $this->expectExceptionMessage('Queue name must be specified');

        AmqpQueue::default('');
    }

    public function createWithToLongName(): void
    {
        $this->expectException(InvalidQueueName::class);
        $this->expectExceptionMessage('Queue name may be up to 255 bytes of UTF-8 characters (260 specified)');

        AmqpQueue::default(\str_repeat('x', 260));
    }

    /**
     * @test
     */
    public function defaultCreate(): void
    {
        $queue = AmqpQueue::default(__METHOD__);

        self::assertSame(__METHOD__, $queue->toString());

        self::assertSame(0, $queue->flags);
    }

    /**
     * @test
     */
    public function delayedCreate(): void
    {
        $queue = AmqpQueue::delayed('test', AmqpExchange::direct('qwerty'))->makeDurable();

        self::assertSame('test', $queue->toString());

        /** @see AmqpQueue::AMQP_DURABLE */
        self::assertSame(2, $queue->flags);
    }

    /**
     * @test
     */
    public function flags(): void
    {
        $queue = AmqpQueue::default(__METHOD__)->makeDurable();

        /** @see AmqpQueue::AMQP_DURABLE */
        self::assertSame(2, $queue->flags);

        /** @see AmqpQueue::AMQP_PASSIVE */
        $queue = $queue->makePassive();
        self::assertSame(6, $queue->flags);

        /** @see AmqpQueue::AMQP_AUTO_DELETE */
        $queue = $queue->enableAutoDelete();
        self::assertSame(22, $queue->flags);

        /** @see AmqpQueue::AMQP_EXCLUSIVE */
        $queue = $queue->makeExclusive();
        self::assertSame(30, $queue->flags);

        $queue = $queue->wthArguments(['key' => 'value']);
        self::assertSame(['key' => 'value'], $queue->arguments);
    }
}
