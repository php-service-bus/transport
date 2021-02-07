<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Common transport implementation interfaces.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
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

        self::assertSame('qwerty', $bind->destinationTopic->toString());
        self::assertSame('key', $bind->routingKey);
    }
}
