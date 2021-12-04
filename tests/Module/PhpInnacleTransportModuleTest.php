<?php

/** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHPinnacle RabbitMQ adapter.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Transport\Tests\Module;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ServiceBus\Transport\Module\PhpInnacleTransportModule;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 *
 */
final class PhpInnacleTransportModuleTest extends TestCase
{
    /**
     * @test
     */
    public function boot(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(['service_bus.logger' => new Definition(NullLogger::class)]);

        $module = new PhpInnacleTransportModule(
            (string) \getenv('TRANSPORT_CONNECTION_DSN'),
            'test',
            'qwerty'
        );

        $module->configureQos(10, 100, true);

        $module->boot($containerBuilder);

        $containerBuilder->compile();

        self::assertTrue(true);
    }
}
