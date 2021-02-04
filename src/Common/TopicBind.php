<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Common;

/**
 * Binding the topic to the topic.
 *
 * @psalm-immutable
 */
class TopicBind
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

    public function __construct(Topic $destinationTopic, ?string $routingKey = null)
    {
        $this->destinationTopic = $destinationTopic;
        $this->routingKey       = $routingKey;
    }
}
