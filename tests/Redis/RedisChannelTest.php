<?php

/** @noinspection PhpUnhandledExceptionInspection */

/**
 * Redis transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Transport\Tests\Redis;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Redis\Exceptions\IncorrectChannelName;
use ServiceBus\Transport\Redis\RedisChannel;

/**
 *
 */
final class RedisChannelTest extends TestCase
{
    /**
     * @test
     */
    public function successCreate(): void
    {
        self::assertSame('qwerty', (new  RedisChannel('qwerty'))->name);
    }

    /**
     * @test
     */
    public function createWithEmptyName(): void
    {
        $this->expectException(IncorrectChannelName::class);

        new RedisChannel('');
    }
}
