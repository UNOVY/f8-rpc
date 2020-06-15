<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace Tests\PHPUnit;

use F8\IO\Interfaces\GuardDispatcherInterface;
use F8\Rpc\BasicRouter;
use F8\Rpc\RpcException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 * @covers \F8\Rpc\BasicRouter
 */
final class BasicRouterTest extends TestCase
{
    use ProphecyTrait;

    public function testAddAndRunningMethod()
    {
        $router = $this->createBasicRouter();

        $guardDispatcher = $this->prophesize(GuardDispatcherInterface::class)->reveal();

        $router->add('method', $guardDispatcher);

        static::assertSame($guardDispatcher, $router->run('method'));
    }

    public function testCannotDeclareDuplicateMethod()
    {
        $this->expectException(RpcException::class);

        $router = $this->createBasicRouter();

        $guardDispatcher0 = $this->prophesize(GuardDispatcherInterface::class)->reveal();
        $guardDispatcher1 = $this->prophesize(GuardDispatcherInterface::class)->reveal();

        $router->add('method', $guardDispatcher0);
        $router->add('method', $guardDispatcher1);
    }

    public function testCannotDeclareDuplicateMethod2()
    {
        $this->expectException(RpcException::class);

        $guardDispatcher0 = $this->prophesize(GuardDispatcherInterface::class)->reveal();
        $guardDispatcher1 = $this->prophesize(GuardDispatcherInterface::class)->reveal();

        $router = $this->createBasicRouter([
            'method' => $guardDispatcher0,
        ]);

        $router->add('method', $guardDispatcher1);
    }

    public function testMethodNotFound()
    {
        $this->expectException(RpcException::class);
        $this->expectExceptionCode(RpcException::METHOD_NOT_FOUND);

        $router = $this->createBasicRouter();

        $router->run('method');
    }

    private function createBasicRouter(array $map = []): BasicRouter
    {
        return new BasicRouter($map);
    }
}
