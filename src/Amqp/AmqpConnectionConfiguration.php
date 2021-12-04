<?php

/**
 * AMQP transport implementation.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Amqp;

use ServiceBus\Transport\Common\Exceptions\InvalidConnectionParameters;

/**
 * Amqp connection details.
 */
final class AmqpConnectionConfiguration
{
    private const DEFAULT_SCHEMA = 'amqp';

    private const DEFAULT_HOST = 'localhost';

    private const DEFAULT_PORT = 5672;

    private const DEFAULT_USERNAME = 'guest';

    private const DEFAULT_PASSWORD = 'guest';

    private const DEFAULT_TIMEOUT = 5000;

    private const DEFAULT_HEARTBEAT_INTERVAL = 60000;

    private const DEFAULT_VIRTUAL_HOST = '/';

    /**
     * Connection DSN parameters bag.
     *
     * Created from array with keys:
     *
     * @psalm-var array{
     *    scheme:string,
     *    user:string,
     *    password:string,
     *    host:string,
     *    port:int,
     *    vhost:string,
     *    timeout:int,
     *    heartbeat:int
     * }
     *
     * @var array
     */
    public $parameters;

    /**
     * @psalm-param non-empty-string $connectionDSN DSN example: amqp://user:password@host:port
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\InvalidConnectionParameters Incorrect DSN
     */
    public function __construct(string $connectionDSN)
    {
        $this->parameters = self::extractConnectionParameters($connectionDSN);
    }

    public static function createLocalhost(): self
    {
        return new self('amqp://guest:guest@localhost:5672');
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s://%s:%s@%s:%s?vhost=%s&timeout=%d&heartbeat=%d',
            $this->parameters['scheme'],
            $this->parameters['user'],
            $this->parameters['password'],
            $this->parameters['host'],
            $this->parameters['port'],
            $this->parameters['vhost'],
            $this->parameters['timeout'],
            $this->parameters['heartbeat']
        );
    }

    /**
     * @psalm-return array{
     *   scheme:string,
     *   user:string,
     *   password:string,
     *   host:string,
     *   port:int,
     *   vhost:string,
     *   timeout:int,
     *   heartbeat:int
     * }
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\InvalidConnectionParameters Incorrect DSN
     */
    private static function extractConnectionParameters(string $connectionDSN): array
    {
        $connectionParts = self::parseUrl($connectionDSN);

        $queryString = (string) ($connectionParts['query'] ?? '');

        $queryParts = self::parseQuery($queryString);

        return [
            'scheme'    => (string) ($connectionParts['scheme'] ?? self::DEFAULT_SCHEMA),
            'host'      => (string) ($connectionParts['host'] ?? self::DEFAULT_HOST),
            'port'      => (int) ($connectionParts['port'] ?? self::DEFAULT_PORT),
            'user'      => (string) ($connectionParts['user'] ?? self::DEFAULT_USERNAME),
            'password'  => (string) ($connectionParts['pass'] ?? self::DEFAULT_PASSWORD),
            'timeout'   => (int) ($queryParts['timeout'] ?? self::DEFAULT_TIMEOUT),
            'vhost'     => (string) ($queryParts['vhost'] ?? self::DEFAULT_VIRTUAL_HOST),
            'heartbeat' => (int) ($queryParts['heartbeat'] ?? self::DEFAULT_HEARTBEAT_INTERVAL),
        ];
    }

    /**
     * Parse connection DSN parts.
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\InvalidConnectionParameters Incorrect DSN
     */
    private static function parseUrl(string $url): array
    {
        if ($url === '')
        {
            throw InvalidConnectionParameters::emptyDSN();
        }

        $parsedParts = \parse_url($url);

        if ($parsedParts !== false)
        {
            return $parsedParts;
        }

        throw InvalidConnectionParameters::incorrectDSN($url);
    }

    /**
     * Parse url query parts.
     *
     * @psalm-return array<string, string|int|float>
     */
    private static function parseQuery(string $query): array
    {
        $output = [];

        \parse_str($query, $output);

        /** @psalm-var array<string, string|int|float> $output */

        return $output;
    }
}
