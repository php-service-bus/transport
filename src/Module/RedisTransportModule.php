<?php

/**
 * Redis transport implementation module.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Module;

use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Transport;
use ServiceBus\Transport\Redis\RedisTransport;
use ServiceBus\Transport\Redis\RedisTransportConnectionConfiguration;
use ServiceBus\Transport\Redis\RedisTransportLevelDestination;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @api
 */
final class RedisTransportModule implements ServiceBusModule
{
    /** @var string */
    private $connectionDSN;

    /** @var string */
    private $defaultDestinationChannel;

    /**
     * Connection DSN example: tcp://test-host:6379?timeout=10&password=qwerty.
     */
    public function __construct(string $connectionDSN, string $defaultDestinationChannel)
    {
        $this->connectionDSN             = $connectionDSN;
        $this->defaultDestinationChannel = $defaultDestinationChannel;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerBuilder $containerBuilder): void
    {
        $this->injectParameters($containerBuilder);

        /** Default transport level destination */
        $destinationDefinition = new Definition(RedisTransportLevelDestination::class, [
            '%service_bus.transport.redis.default_destination_channel%',
        ]);

        /** Redis connection config */
        $connectionConfigDefinition = new Definition(RedisTransportConnectionConfiguration::class, [
            '%service_bus.transport.redis.dsn%',
        ]);

        $containerBuilder->addDefinitions([
            DeliveryDestination::class                   => $destinationDefinition,
            RedisTransportConnectionConfiguration::class => $connectionConfigDefinition,
            Transport::class                             => new Definition(RedisTransport::class, [
                new Reference(RedisTransportConnectionConfiguration::class),
                new Reference('service_bus.logger'),
            ]),
        ]);
    }

    /**
     * Push parameters to container.
     */
    private function injectParameters(ContainerBuilder $containerBuilder): void
    {
        $parameters = [
            'service_bus.transport.redis.dsn'                         => $this->connectionDSN,
            'service_bus.transport.redis.default_destination_channel' => $this->defaultDestinationChannel,
        ];

        foreach ($parameters as $key => $value)
        {
            $containerBuilder->setParameter($key, $value);
        }
    }
}
