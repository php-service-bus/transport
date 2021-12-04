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
 * Queue interface.
 */
interface Queue
{
    /**
     * Return queue name.
     *
     * @psalm-return non-empty-string
     */
    public function toString(): string;
}
