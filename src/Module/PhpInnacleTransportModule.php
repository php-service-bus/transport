<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Module;

use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use ServiceBus\Transport\Amqp\AmqpQoSConfiguration;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Transport;
use ServiceBus\Transport\Amqp\PhpInnacle\PhpInnacleTransport;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @api
 */
final class PhpInnacleTransportModule implements ServiceBusModule
{
    /** @var string */
    private $connectionDSN;

    /** @var string */
    private $defaultDestinationExchange;

    /** @var string|null */
    private $defaultDestinationRoutingKey;

    /** @var int|null */
    private $qosSize;

    /** @var int|null */
    private $qosCount;

    /** @var bool|null */
    private $qosGlobal;

    public function __construct(string $connectionDSN, string $defaultDestinationExchange, ?string $defaultDestinationRoutingKey)
    {
        $this->connectionDSN                = $connectionDSN;
        $this->defaultDestinationExchange   = $defaultDestinationExchange;
        $this->defaultDestinationRoutingKey = $defaultDestinationRoutingKey;
    }

    /**
     * Apply Quality Of Service settings.
     */
    public function configureQos(?int $size = null, ?int $count = null, ?bool $isGlobal = null): void
    {
        $this->qosSize   = $size;
        $this->qosCount  = $count;
        $this->qosGlobal = $isGlobal;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerBuilder $containerBuilder): void
    {
        $this->injectParameters($containerBuilder);

        /** Default transport level destination */
        $destinationDefinition = new Definition(AmqpTransportLevelDestination::class, [
            '%service_bus.transport.amqp.default_destination_topic%',
            '%service_bus.transport.amqp.default_destination_key%',
        ]);

        $containerBuilder->addDefinitions([DeliveryDestination::class => $destinationDefinition]);

        /** RabbitMQ connection config */
        $connectionConfigDefinition = new Definition(AmqpConnectionConfiguration::class, [
            '%service_bus.transport.amqp.dsn%',
        ]);

        $containerBuilder->addDefinitions([AmqpConnectionConfiguration::class => $connectionConfigDefinition]);

        /** RabbitMQ QoS settings */
        $qosConfigurationDefinition = new Definition(AmqpQoSConfiguration::class, [
            '%service_bus.transport.amqp.qos_size%',
            '%service_bus.transport.amqp.qos_count%',
            '%service_bus.transport.amqp.qos_global%',
        ]);

        $containerBuilder->addDefinitions([AmqpQoSConfiguration::class => $qosConfigurationDefinition]);

        /** RabbitMQ transport implementation */
        $transportDefinition = new Definition(PhpInnacleTransport::class, [
            new Reference(AmqpConnectionConfiguration::class),
            new Reference(AmqpQoSConfiguration::class),
            new Reference('service_bus.logger'),
        ]);

        $containerBuilder->addDefinitions([Transport::class => $transportDefinition]);
    }

    /**
     * Push parameters to container.
     */
    private function injectParameters(ContainerBuilder $containerBuilder): void
    {
        $parameters = [
            'service_bus.transport.amqp.dsn'                       => $this->connectionDSN,
            'service_bus.transport.amqp.qos_size'                  => $this->qosSize,
            'service_bus.transport.amqp.qos_count'                 => $this->qosCount,
            'service_bus.transport.amqp.qos_global'                => $this->qosGlobal,
            'service_bus.transport.amqp.default_destination_topic' => $this->defaultDestinationExchange,
            'service_bus.transport.amqp.default_destination_key'   => $this->defaultDestinationRoutingKey,
        ];

        foreach ($parameters as $key => $value)
        {
            $containerBuilder->setParameter($key, $value);
        }
    }
}
