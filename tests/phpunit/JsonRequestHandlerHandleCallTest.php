<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace Tests\PHPUnit;

use Exception;
use F8\IO\GuardDispatcher;
use F8\IO\Interfaces\ContextFactoryInterface;
use F8\IO\Interfaces\ContextInterface;
use F8\IO\Interfaces\InputInterface;
use F8\IO\Interfaces\OutputInterface;
use F8\IO\Interfaces\TaskInterface;
use F8\IO\TaskHandler;
use F8\Rpc\BasicRouter;
use F8\Rpc\ErrorResponse;
use F8\Rpc\JsonRequestHandler;
use F8\Rpc\Notification;
use F8\Rpc\Request;
use F8\Rpc\ResultResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 * @covers \F8\Rpc\BasicRouter
 */
final class JsonRequestHandlerHandleCallTest extends TestCase
{
    use ProphecyTrait;

    public function testNotificationCallReturnsNothing()
    {
        $requestHandler = $this->createRequestHandler([
            'subtract' => new GuardDispatcher(new TaskHandler($this->createSubtractTask())),
        ]);

        $response = $requestHandler->handleCall($this->createNotification());

        static::assertNull($response);
    }

    public function testRequestCallReturnsResponse()
    {
        $requestHandler = $this->createRequestHandler([
            'subtract' => new GuardDispatcher(new TaskHandler($this->createSubtractTask())),
        ]);

        $request = $this->createRequest();

        $response = $requestHandler->handleCall($request);

        static::assertInstanceOf(ResultResponse::class, $response);
        static::assertSame($request->getId(), $response->getId());
    }

    public function testThrowingTaskReturnsErrorResponse()
    {
        $requestHandler = $this->createRequestHandler([
            'subtract' => new GuardDispatcher(new TaskHandler($this->createThrowingTask())),
        ]);

        $request = $this->createRequest();

        $response = $requestHandler->handleCall($request);

        static::assertInstanceOf(ErrorResponse::class, $response);
        static::assertSame($request->getId(), $response->getId());
    }

    private function createRequest()
    {
        return new Request(1, 'subtract', ['subtrahend' => 23, 'minuend' => 42]);
    }

    private function createNotification()
    {
        return new Notification('subtract', ['subtrahend' => 23, 'minuend' => 42]);
    }

    private function createSubtractTask()
    {
        $outputProphecy = $this->prophesize(OutputInterface::class);

        $taskProphecy = $this->prophesize(TaskInterface::class);
        $taskProphecy->do(
            Argument::type(ContextInterface::class),
            Argument::type(InputInterface::class),
        )->will(function ($args) use ($outputProphecy) {
            list($ctx, $i) = $args;

            $result = $i->get('minuend') - $i->get('subtrahend');

            $outputProphecy->jsonSerialize()->willReturn($result);

            return $outputProphecy->reveal();
        });

        return $taskProphecy->reveal();
    }

    private function createThrowingTask()
    {
        $taskProphecy = $this->prophesize(TaskInterface::class);
        $taskProphecy->do(
            Argument::type(ContextInterface::class),
            Argument::type(InputInterface::class),
        )->will(function ($args) {
            throw new Exception('Throwing Task');
        });

        return $taskProphecy->reveal();
    }

    private function createRequestHandler(array $map = []): JsonRequestHandler
    {
        $ctxProphecy = $this->prophesize(ContextInterface::class);
        $ctxFactoryProphecy = $this->prophesize(ContextFactoryInterface::class);
        $ctxFactoryProphecy->createContext()->willReturn($ctxProphecy->reveal());

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse(
            Argument::type('int'),
            Argument::type('string'),
        )->willReturn($responseProphecy->reveal());

        return new JsonRequestHandler(
            new BasicRouter($map),
            $ctxFactoryProphecy->reveal(),
            $responseFactoryProphecy->reveal(),
        );
    }
}
