<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Common;

/**
 * Binding the queue to the topic.
 *
 * @psalm-immutable
 */
class QueueBind
{
    /**
     * The topic to which the binding is going.
     *
     * @psalm-readonly
     *
     * @var Topic
     */
    public $destinationTopic;

    /**
     * Binding Key.
     *
     * @psalm-readonly
     *
     * @var string|null
     */
    public $routingKey;

    /**
     * Binding Arguments.
     *
     * @psalm-readonly
     * @psalm-var array<string, int|float|string|null>
     */
    public $arguments;

    /**
     * @psalm-param non-empty-string|null                $routingKey
     * @psalm-param array<string, int|float|string|null> $arguments
     */
    public function __construct(Topic $destinationTopic, ?string $routingKey = null, array $arguments = [])
    {
        $this->destinationTopic = $destinationTopic;
        $this->routingKey       = $routingKey;
        $this->arguments        = $arguments;
    }
}
