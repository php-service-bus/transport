<?php

/**
 * Common transport implementation interfaces.
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
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

        self::assertSame('payloadData', $package->payload);
        self::assertSame(['key' => 'value'], $package->headers);
        self::assertSame($destination, $package->destination);
        self::assertSame('traceId', $package->traceId);
    }
}
