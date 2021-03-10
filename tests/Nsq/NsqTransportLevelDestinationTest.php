<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Nsq transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Nsq;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Nsq\Exceptions\IncorrectChannelName;
use ServiceBus\Transport\Nsq\NsqTransportLevelDestination;

/**
 *
 */
final class NsqTransportLevelDestinationTest extends TestCase
{
    /**
     * @test
     */
    public function successCreate(): void
    {
        self::assertSame('qwerty', (new NsqTransportLevelDestination('qwerty'))->channel);
    }

    /**
     * @test
     */
    public function createWithEmptyName(): void
    {
        $this->expectException(IncorrectChannelName::class);

        new NsqTransportLevelDestination('');
    }
}
