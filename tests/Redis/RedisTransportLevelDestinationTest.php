<?php

/**
 * Redis transport implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Redis;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Redis\Exceptions\IncorrectChannelName;
use ServiceBus\Transport\Redis\RedisTransportLevelDestination;

/**
 *
 */
final class RedisTransportLevelDestinationTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     */
    public function successCreate(): void
    {
        static::assertSame('qwerty', (new RedisTransportLevelDestination('qwerty'))->channel);
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function createWithEmptyName(): void
    {
        $this->expectException(IncorrectChannelName::class);

        new RedisTransportLevelDestination('');
    }
}
