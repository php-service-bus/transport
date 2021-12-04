<?php

/**
 * AMQP transport implementation.
 *
 * @author  Konstantin  Grachev <me@grachevko.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Transport\Nsq;

use ServiceBus\Transport\Nsq\Exceptions\IncorrectConnectionParameters;
use function ServiceBus\Transport\Common\parseConnectionDSN;
use function ServiceBus\Transport\Common\parseConnectionQuery;

/**
 * Connection parameters.
 */
final class NsqTransportConnectionConfiguration
{
    private const DEFAULT_HOST = 'localhost';

    private const DEFAULT_PORT = 4150;

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
     * @throws \ServiceBus\Transport\Nsq\Exceptions\IncorrectConnectionParameters
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
        $query      = parseConnectionQuery($parameters['query'] ?? '');

        $this->scheme  = $parameters['scheme'];
        $this->host    = !empty($parameters['host']) ? $parameters['host'] : self::DEFAULT_HOST;
        $this->port    = !empty($parameters['port']) ? $parameters['port'] : self::DEFAULT_PORT;
        $this->timeout = !empty($query['timeout']) ? (int) $query['timeout'] : self::DEFAULT_TIMEOUT;

        if ($this->timeout < 0)
        {
            $this->timeout = self::DEFAULT_TIMEOUT;
        }
    }

    public function toString(): string
    {
        return \sprintf(
            '%s://%s:%s',
            $this->scheme,
            $this->host,
            $this->port,
        );
    }
}
