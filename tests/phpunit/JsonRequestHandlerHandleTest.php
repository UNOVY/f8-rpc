<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace Tests\PHPUnit;

use F8\IO\GuardDispatcher;
use F8\IO\Interfaces\ContextFactoryInterface;
use F8\IO\Interfaces\ContextInterface;
use F8\IO\Interfaces\InputInterface;
use F8\IO\Interfaces\OutputInterface;
use F8\IO\Interfaces\TaskInterface;
use F8\IO\TaskHandler;
use F8\Rpc\BasicRouter;
use F8\Rpc\JsonRequestHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 * @covers \F8\Rpc\BasicRouter
 */
final class JsonRequestHandlerHandleTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider provideJsonRpcRequestBody
     */
    public function testHandlesJsonRpcRequest(string $json, string $expectedJson)
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($json);

        $response = $this->createRequestHandler()->handle($requestProphecy->reveal());

        $outputJson = (string) $response->getBody();

        static::assertSame($expectedJson, $outputJson);
    }

    public function provideJsonRpcRequestBody()
    {
        return [
            'rpc call with named parameters' => [
                '{"jsonrpc":"2.0","method":"subtract","params":{"subtrahend":23,"minuend":42},"id":3}',
                '{"jsonrpc":"2.0","result":19,"id":3}',
            ],
            'a Notification #0' => [
                '{"jsonrpc": "2.0", "method": "update", "params": [1,2,3,4,5]}',
                '',
            ],
            'a Notification #1' => [
                '{"jsonrpc": "2.0", "method": "foobar"}',
                '',
            ],
            'rpc call of non-existent method' => [
                '{"jsonrpc": "2.0", "method": "foobar", "id": "1"}',
                '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":"1"}',
            ],
            'rpc call with invalid JSON' => [
                '{"jsonrpc": "2.0", "method": "foobar, "params": "bar", "baz]',
                '{"jsonrpc":"2.0","error":{"code":-32700,"message":"Error decoding JSON."},"id":null}',
            ],
            'rpc call with invalid Request object' => [
                '{"jsonrpc": "2.0", "method": 1, "params": "bar"}',
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request: Expected member \"method\" to be string."},"id":null}',
            ],
            'rpc call with invalid Request object' => [
                '[
                    {"jsonrpc": "2.0", "method": "sum", "params": [1,2,4], "id": "1"},
                    {"jsonrpc": "2.0", "method"
                ]',
                '{"jsonrpc":"2.0","error":{"code":-32700,"message":"Error decoding JSON."},"id":null}',
            ],
            'rpc call with an empty Array' => [
                '[]',
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request: Expected at least one valid Request or Notification."},"id":null}',
            ],
            'rpc call with an invalid Batch (but not empty)' => [
                '[1]',
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request: Expected at least one valid Request or Notification."},"id":null}',
            ],
            'rpc call with an invalid Batch (but not empty)' => [
                '[1,2,3]',
                '[{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null},{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null},{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null}]',
            ],
            'rpc call Batch' => [
                '[
                    {"jsonrpc": "2.0", "method": "sum", "params": {"a": 42, "b": 35}, "id": "1"},
                    {"jsonrpc": "2.0", "method": "notify_hello", "params": [7]},
                    {"jsonrpc": "2.0", "method": "subtract", "params": {"subtrahend": 23, "minuend": 42}, "id": "2"},
                    {"foo": "boo"},
                    {"jsonrpc": "2.0", "method": "method-not-found", "params": {"name": "myself"}, "id": "5"},
                    {"jsonrpc": "2.0", "method": "request_hello", "id": "9"}
                ]',
                '[{"jsonrpc":"2.0","result":77,"id":"1"},{"jsonrpc":"2.0","result":19,"id":"2"},{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request: Missing required member \"method\"."},"id":null},{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":"5"},{"jsonrpc":"2.0","result":["hello",5],"id":"9"}]',
            ],
        ];
    }

    private function createAdditionTask()
    {
        $outputProphecy = $this->prophesize(OutputInterface::class);

        $taskProphecy = $this->prophesize(TaskInterface::class);
        $taskProphecy->do(
            Argument::type(ContextInterface::class),
            Argument::type(InputInterface::class),
        )->will(function ($args) use ($outputProphecy) {
            list($ctx, $i) = $args;

            $result = $i->get('a') + $i->get('b');

            $outputProphecy->jsonSerialize()->will(function ($args) use ($result) {
                return $result;
            });

            return $outputProphecy->reveal();
        });

        return $taskProphecy->reveal();
    }

    private function createHelloTask()
    {
        $outputProphecy = $this->prophesize(OutputInterface::class);

        $taskProphecy = $this->prophesize(TaskInterface::class);
        $taskProphecy->do(
            Argument::type(ContextInterface::class),
            Argument::type(InputInterface::class),
        )->will(function ($args) use ($outputProphecy) {
            $outputProphecy->jsonSerialize()->willReturn(['hello', 5]);

            return $outputProphecy->reveal();
        });

        return $taskProphecy->reveal();
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

            $outputProphecy->jsonSerialize()->will(function ($args) use ($result) {
                return $result;
            });

            return $outputProphecy->reveal();
        });

        return $taskProphecy->reveal();
    }

    private function createRequestHandler(array $map = []): JsonRequestHandler
    {
        $ctxProphecy = $this->prophesize(ContextInterface::class);
        $ctxFactoryProphecy = $this->prophesize(ContextFactoryInterface::class);
        $ctxFactoryProphecy->createContext()->willReturn($ctxProphecy->reveal());

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->write(Argument::type('string'))->will(function ($args) {
            $this->body = $args[0];
        });
        $bodyProphecy->__toString()->will(function ($args) {
            return $this->body;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn($bodyProphecy->reveal());
        $responseProphecy->withHeader(
            Argument::type('string'),
            Argument::type('string')
        )->will(function () { return $this; });

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy->createResponse(
            Argument::type('int'),
            Argument::type('string'),
        )->willReturn($responseProphecy->reveal());

        return new JsonRequestHandler(
            new BasicRouter(array_merge([
                'sum' => new GuardDispatcher(new TaskHandler($this->createAdditionTask())),
                'subtract' => new GuardDispatcher(new TaskHandler($this->createSubtractTask())),
                'request_hello' => new GuardDispatcher(new TaskHandler($this->createHelloTask())),
            ]), $map),
            $ctxFactoryProphecy->reveal(),
            $responseFactoryProphecy->reveal(),
        );
    }
}
