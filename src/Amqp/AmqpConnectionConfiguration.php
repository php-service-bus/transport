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

use function ServiceBus\Transport\Common\parseConnectionDSN;
use function ServiceBus\Transport\Common\parseConnectionQuery;

/**
 * Amqp connection details.
 */
final class AmqpConnectionConfiguration
{
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
     * @throws \ServiceBus\Transport\Common\Exceptions\IncorrectConnectionParameters Incorrect DSN
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
     * @psalm-param non-empty-string $connectionDSN
     *
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
     * @throws \ServiceBus\Transport\Common\Exceptions\IncorrectConnectionParameters
     */
    private static function extractConnectionParameters(string $connectionDSN): array
    {
        $connectionParts = parseConnectionDSN($connectionDSN);

        /**
         * @psalm-var array{
         *     timeout:numeric-string|null,
         *     vhost:non-empty-string|null,
         *     heartbeat:numeric-string|null
         * } $parsedQuery
         */
        $parsedQuery = parseConnectionQuery($connectionParts['query'] ?? '');

        return [
            'scheme'    => $connectionParts['scheme'],
            'host'      => $connectionParts['host'] ?? self::DEFAULT_HOST,
            'port'      => $connectionParts['port'] ?? self::DEFAULT_PORT,
            'user'      => $connectionParts['user'] ?? self::DEFAULT_USERNAME,
            'password'  => $connectionParts['pass'] ?? self::DEFAULT_PASSWORD,
            'timeout'   => (int) ($parsedQuery['timeout'] ?? self::DEFAULT_TIMEOUT),
            'vhost'     => $parsedQuery['vhost'] ?? self::DEFAULT_VIRTUAL_HOST,
            'heartbeat' => (int) ($parsedQuery['heartbeat'] ?? self::DEFAULT_HEARTBEAT_INTERVAL),
        ];
    }
}
