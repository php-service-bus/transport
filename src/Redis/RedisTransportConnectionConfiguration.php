<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Transport\Redis;

use ServiceBus\Transport\Redis\Exceptions\IncorrectConnectionParameters;

/**
 * Connection parameters.
 *
 * @psalm-immutable
 */
final class RedisTransportConnectionConfiguration
{
    private const DEFAULT_HOST = 'localhost';

    private const DEFAULT_PORT = 6379;

    private const DEFAULT_TIMEOUT = 5;

    /**
     * @psalm-readonly
     *
     * @var string
     */
    public $scheme;

    /**
     * @psalm-readonly
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
     *
     * @var string|null
     */
    public $password;

    /**
     * @throws \ServiceBus\Transport\Redis\Exceptions\IncorrectConnectionParameters
     */
    public function __construct(string $connectionDSN)
    {
        $parameters = self::parseUrl($connectionDSN);

        $queryString = (string) ($parameters['query'] ?? '');

        $query = self::parseQuery($queryString);

        $this->scheme   = (string) $parameters['scheme'];
        $this->host     = isset($parameters['host']) ? (string) $parameters['host'] : self::DEFAULT_HOST;
        $this->port     = isset($parameters['port']) ? (int) $parameters['port'] : self::DEFAULT_PORT;
        $this->password = isset($query['password']) ? (string) $query['password'] : null;
        $this->timeout  = isset($query['timeout']) ? (int) $query['timeout'] : self::DEFAULT_TIMEOUT;

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

    /**
     * @throws \ServiceBus\Transport\Redis\Exceptions\IncorrectConnectionParameters
     */
    private static function parseUrl(string $connectionDSN): array
    {
        if ($connectionDSN === '')
        {
            throw IncorrectConnectionParameters::connectionDsnCantBeEmpty();
        }

        if (!\str_starts_with($connectionDSN, 'tcp://') && !\str_starts_with($connectionDSN, 'unix://'))
        {
            throw IncorrectConnectionParameters::incorrectScheme();
        }

        $parsedParts = \parse_url($connectionDSN);

        if ($parsedParts !== false)
        {
            return $parsedParts;
        }

        throw IncorrectConnectionParameters::incorrectDSN($connectionDSN);
    }

    /**
     * @psalm-return array<string, string|int|float>
     */
    private static function parseQuery(string $connectionDSN): array
    {
        $output = [];

        \parse_str($connectionDSN, $output);

        /** @psalm-var array<string, string|int|float> $output */

        return $output;
    }
}
