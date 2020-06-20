<?php

/**
 * Common transport implementation interfaces.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Common;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\Topic;

/**
 *
 */
final class QueueBindTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function create(): void
    {
        $topic = new class() implements Topic
        {
            public function __toString(): string
            {
                return 'qwerty';
            }

            public function toString(): string
            {
                return 'qwerty';
            }
        };

        $bind = new QueueBind($topic, 'key');

        static::assertSame('qwerty', $bind->destinationTopic->toString());
        static::assertSame('key', $bind->routingKey);
    }
}
