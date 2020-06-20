<?php

/**
 * AMQP transport common implementation.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Amqp;

/**
 * Quality Of Service settings.
 *
 * @psalm-readonly
 */
final class AmqpQoSConfiguration
{
    private const DEFAULT_QOS_PRE_FETCH_SIZE = 0;

    private const DEFAULT_QOS_PRE_FETCH_COUNT = 100;

    private const DEFAULT_QOS_GLOBAL = false;

    /**
     * The client can request that messages be sent in advance so that when the client finishes processing a message,
     * the following message is already held locally, rather than needing to be sent down the channel. Prefetching
     * gives a performance improvement. This field specifies the prefetch window size in octets. The server will send
     * a message in advance if it is equal to or smaller in size than the available prefetch size (and also falls into
     * other prefetch limits). May be set to zero, meaning "no specific limit", although other prefetch limits may
     * still apply. The prefetch-size is ignored if the no-ack option is set.
     *
     * The server MUST ignore this setting when the client is not processing any messages - i.e. the prefetch size does
     * not limit the transfer of single messages to a client, only the sending in advance of more messages while the
     * client still has one or more unacknowledged messages.
     *
     * @var int
     */
    public $size;

    /**
     * Specifies a prefetch window in terms of whole messages. This field may be used in combination with the
     * prefetch-size field; a message will only be sent in advance if both prefetch windows (and those at the channel
     * and connection level) allow it. The prefetch-count is ignored if the no-ack option is set.
     *
     * The server may send less data in advance than allowed by the client's specified prefetch windows but it MUST NOT
     * send more.
     *
     * @var int
     */
    public $count;

    /**
     * RabbitMQ has reinterpreted this field. The original specification said: "By default the QoS settings apply to
     * the current channel only. If this field is set, they are applied to the entire connection." Instead, RabbitMQ
     * takes global=false to mean that the QoS settings should apply per-consumer (for new consumers on the channel;
     * existing ones being unaffected) and global=true to mean that the QoS settings should apply per-channel.
     *
     * @var bool
     */
    public $global;

    public function __construct(?int $size = null, ?int $count = null, ?bool $global = null)
    {
        $size  = $size ?? self::DEFAULT_QOS_PRE_FETCH_SIZE;
        $count = $count ?? self::DEFAULT_QOS_PRE_FETCH_COUNT;

        $this->size   = 0 > $size ? self::DEFAULT_QOS_PRE_FETCH_SIZE : $size;
        $this->count  = 0 > $count ? self::DEFAULT_QOS_PRE_FETCH_COUNT : $count;
        $this->global = $global ?? self::DEFAULT_QOS_GLOBAL;
    }
}
