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

use Amp\Loop;
use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Nsq\NsqChannel;
use ServiceBus\Transport\Nsq\NsqConsumer;
use ServiceBus\Transport\Nsq\NsqTransportConnectionConfiguration;

/**
 *
 */
final class NsqConsumerTest extends TestCase
{
    /**
     * @var NsqTransportConnectionConfiguration
     */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new NsqTransportConnectionConfiguration((string) \getenv('NSQ_CONNECTION_DSN'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->config);
    }

    /**
     * @test
     */
    public function disconnectWithoutConsuming(): void
    {
        Loop::run(
            function (): \Generator
            {
                $consumer = new NsqConsumer(new NsqChannel('qwerty'), $this->config);

                yield $consumer->stop();
            }
        );
    }
}
