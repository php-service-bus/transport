<?php

/**
 * AMQP transport implementation.
 *
 * @author  Konstantin  Grachev <me@grachevko.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Module;

use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Transport;
use ServiceBus\Transport\Nsq\NsqTransport;
use ServiceBus\Transport\Nsq\NsqTransportConnectionConfiguration;
use ServiceBus\Transport\Nsq\NsqTransportLevelDestination;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @api
 */
final class NsqTransportModule implements ServiceBusModule
{
    /**
     * @var string
     */
    private $connectionDSN;

    /**
     * @var string
     */
    private $defaultDestinationChannel;

    /**
     * Connection DSN example: tcp://localhost:4150.
     */
    public function __construct(string $connectionDSN, string $defaultDestinationChannel)
    {
        $this->connectionDSN             = $connectionDSN;
        $this->defaultDestinationChannel = $defaultDestinationChannel;
    }

    public function boot(ContainerBuilder $containerBuilder): void
    {
        $this->injectParameters($containerBuilder);

        /** Default transport level destination */
        $destinationDefinition = new Definition(NsqTransportLevelDestination::class, [
            '%service_bus.transport.nsq.default_destination_channel%',
        ]);

        /** Nsq connection config */
        $connectionConfigDefinition = new Definition(NsqTransportConnectionConfiguration::class, [
            '%service_bus.transport.nsq.dsn%',
        ]);

        $containerBuilder->addDefinitions([
            DeliveryDestination::class                   => $destinationDefinition,
            NsqTransportConnectionConfiguration::class => $connectionConfigDefinition,
            Transport::class                             => new Definition(NsqTransport::class, [
                new Reference(NsqTransportConnectionConfiguration::class),
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
            'service_bus.transport.nsq.dsn'                         => $this->connectionDSN,
            'service_bus.transport.nsq.default_destination_channel' => $this->defaultDestinationChannel,
        ];

        foreach ($parameters as $key => $value)
        {
            $containerBuilder->setParameter($key, $value);
        }
    }
}
