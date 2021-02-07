<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Redis transport implementation module.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Module;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Common\Transport;
use ServiceBus\Transport\Module\RedisTransportModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 *
 */
final class RedisTransportModuleTest extends TestCase
{
    /**
     * @test
     */
    public function boot(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(['service_bus.logger' => new Definition(NullLogger::class)]);

        $module = new RedisTransportModule(
            (string) \getenv('REDIS_CONNECTION_DSN'),
            'testChannel'
        );

        $module->boot($containerBuilder);

        $containerBuilder->getDefinition(Transport::class)->setPublic(true);

        $containerBuilder->compile();

        $containerBuilder->get(Transport::class);

        self::assertTrue(true);
    }
}
