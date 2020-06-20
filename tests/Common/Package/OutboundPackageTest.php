<?php

/**
 * Common transport implementation interfaces.
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Transport\Tests\Common\Package;

use PHPUnit\Framework\TestCase;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Package\OutboundPackage;

/**
 *
 */
class OutboundPackageTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function create(): void
    {
        $destination = new class() implements DeliveryDestination
        {
        };

        $package = new OutboundPackage('payloadData', ['key' => 'value'], $destination, 'traceId');

        static::assertSame('payloadData', $package->payload);
        static::assertSame(['key' => 'value'], $package->headers);
        static::assertSame($destination, $package->destination);
        static::assertSame('traceId', $package->traceId);
    }
}
