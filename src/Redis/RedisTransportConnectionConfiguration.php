<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Redis;

use ServiceBus\Transport\Common\Exceptions\IncorrectConnectionParameters;
use function ServiceBus\Transport\Common\parseConnectionDSN;
use function ServiceBus\Transport\Common\parseConnectionQuery;

/**
 * Connection parameters.
 */
final class RedisTransportConnectionConfiguration
{
    private const DEFAULT_HOST = 'localhost';

    private const DEFAULT_PORT = 6379;

    private const DEFAULT_TIMEOUT = 5;

    /**
     * @psalm-readonly
     * @psalm-var non-empty-string
     *
     * @var string
     */
    public $scheme;

    /**
     * @psalm-readonly
     * @psalm-var non-empty-string
     *
     * @var string
     */
    public $host;

    /**
     * @psalm-readonly
     *
     * @var int
     */
    public $port;

    /**
     * @psalm-readonly
     *
     * @var int
     */
    public $timeout;

    /**
     * @psalm-readonly
     * @psalm-var non-empty-string|null
     *
     * @var string|null
     */
    public $password;

    /**
     * @throws \ServiceBus\Transport\Common\Exceptions\IncorrectConnectionParameters
     */
    public function __construct(string $connectionDSN)
    {
        if ($connectionDSN === '')
        {
            throw IncorrectConnectionParameters::connectionDsnCantBeEmpty();
        }

        if (!\str_starts_with($connectionDSN, 'tcp://') && !\str_starts_with($connectionDSN, 'unix://'))
        {
            throw IncorrectConnectionParameters::incorrectScheme();
        }

        $parameters = parseConnectionDSN($connectionDSN);

        /**
         * @psalm-var array{
         *     password:non-empty-string|null,
         *     timeout:int|null
         * } $queryParameters
         */
        $queryParameters = parseConnectionQuery($parameters['query'] ?? '');

        $this->scheme   = $parameters['scheme'];
        $this->host     = !empty($parameters['host']) ? $parameters['host'] : self::DEFAULT_HOST;
        $this->port     = !empty($parameters['port']) ? $parameters['port'] : self::DEFAULT_PORT;
        $this->password = !empty($queryParameters['password']) ? $queryParameters['password'] : null;
        $this->timeout  = !empty($queryParameters['timeout']) ? $queryParameters['timeout'] : self::DEFAULT_TIMEOUT;

        if ($this->timeout < 0)
        {
            $this->timeout = self::DEFAULT_TIMEOUT;
        }
    }

    public function toString(): string
    {
        return \sprintf(
            '%s://%s:%s?%s',
            $this->scheme,
            $this->host,
            $this->port,
            \http_build_query(\array_filter(['timeout' => $this->timeout * 1000, 'password' => $this->password]))
        );
    }
}
