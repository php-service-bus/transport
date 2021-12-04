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

use ServiceBus\Transport\Redis\Exceptions\IncorrectConnectionParameters;

/**
 * @psalm-param non-empty-string $connectionDSN
 *
 * @psalm-return array{
 *    scheme:non-empty-string,
 *    host:non-empty-string|null,
 *    port:positive-int|null,
 *    user:non-empty-string|null,
 *    pass:non-empty-string|null,
 *    path:non-empty-string|null,
 *    query:non-empty-string|null
 * }
 *
 * @throws \ServiceBus\Transport\Redis\Exceptions\IncorrectConnectionParameters
 */
function parseConnectionDSN(string $connectionDSN): array
{
    /**
     * @psalm-var array{
     *    scheme:non-empty-string|null,
     *    host:non-empty-string|null,
     *    port:positive-int|null,
     *    user:non-empty-string|null,
     *    pass:non-empty-string|null,
     *    path:non-empty-string|null,
     *    query:non-empty-string|null
     * }|null|false $parsedDSN
     */
    $parsedDSN = \parse_url($connectionDSN);

    if (\is_array($parsedDSN))
    {
        if (empty($parsedDSN['scheme']))
        {
            throw IncorrectConnectionParameters::incorrectScheme();
        }

        return $parsedDSN;
    }

    throw IncorrectConnectionParameters::incorrectDSN($connectionDSN);
}

/**
 * @psalm-return array<string, string|int|float>
 */
function parseConnectionQuery(string $connectionQuery): array
{
    $output = [];

    \parse_str($connectionQuery, $output);

    /** @psalm-var array<string, string|int|float> $output */

    return $output;
}
