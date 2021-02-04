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
 * Topic interface.
 */
interface Topic
{
    /**
     * Return topic name.
     */
    public function toString(): string;
}
