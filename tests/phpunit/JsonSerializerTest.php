<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace Tests\PHPUnit;

use F8\Rpc\ErrorResponse;
use F8\Rpc\JsonSerializer;
use F8\Rpc\Notification;
use F8\Rpc\Request;
use F8\Rpc\ResultResponse;
use F8\Rpc\RpcException;
use JsonSerializable;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \F8\Rpc\JsonSerializer
 */
final class JsonSerializerTest extends TestCase
{
    /**
     * @dataProvider provideValidRequests
     * @dataProvider providePassingSampleRequests
     *
     * @param mixed $expectedId
     */
    public function testDeserializeRequestCalls(
        string $json,
        string $expectedMethod,
        array $expectedParams,
        $expectedId
    ) {
        /** @var Request */
        $deserialized = JsonSerializer::deserializeCalls($json, 1);

        static::assertInstanceOf(Request::class, $deserialized);
        static::assertSame($expectedMethod, $deserialized->getMethod());
        static::assertSame($expectedParams, $deserialized->getParams());
        static::assertSame($expectedId, $deserialized->getId());
    }

    /**
     * @dataProvider provideValidNotifications
     * @dataProvider providePassingSampleNotification
     */
    public function testDeserializeNotificationCalls(
        string $json,
        string $expectedMethod,
        array $expectedParams
    ) {
        $deserialized = JsonSerializer::deserializeCalls($json, 1);

        static::assertInstanceOf(Notification::class, $deserialized);
        static::assertSame($expectedMethod, $deserialized->getMethod());
        static::assertSame($expectedParams, $deserialized->getParams());
    }

    /**
     * @dataProvider provideErrorResponses
     * @dataProvider provideResultResponses
     * @dataProvider provideMixedResponses
     */
    public function testSerializeResponse(array $responses, string $expectedJson)
    {
        $json = JsonSerializer::serializeResponses($responses);

        static::assertSame($expectedJson, $json);
    }

    /**
     * @see https://de.wikipedia.org/wiki/JSON-RPC
     */
    public function provideValidRequests(): array
    {
        return [
            [
                '{ "jsonrpc": "2.0", "method": "gibAus", "params": ["Hallo JSON-RPC"], "id": 1}',
                'gibAus',
                ['Hallo JSON-RPC'],
                1,
            ],
            [
                '{ "jsonrpc": "2.0", "method": "gibAus", "params": {"Nachricht": "Hallo JSON-RPC"}, "id": 2}',
                'gibAus',
                ['Nachricht' => 'Hallo JSON-RPC'],
                2,
            ],
            [
                '{ "method": "gibAus", "params": ["Hallo JSON-RPC"], "id": 1}',
                'gibAus',
                ['Hallo JSON-RPC'],
                1,
            ],
            [
                '{"method": "veröffentlicheNachricht", "params": ["Hallo an alle!"], "id": 99}',
                'veröffentlicheNachricht',
                ['Hallo an alle!'],
                99,
            ],
            [
                '{"method": "veröffentlicheNachricht", "params": ["Ich habe eine Frage!"], "id": 101}',
                'veröffentlicheNachricht',
                ['Ich habe eine Frage!'],
                101,
            ],
        ];
    }

    /**
     * @see https://www.jsonrpc.org/specification#examples
     */
    public function providePassingSampleRequests(): array
    {
        return [
            'rpc call with positional parameters #0' => [
                '{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}',
                'subtract',
                [42, 23],
                1,
            ],
            'rpc call with positional parameters #1' => [
                '{"jsonrpc": "2.0", "method": "subtract", "params": [23, 42], "id": 2}',
                'subtract',
                [23, 42],
                2,
            ],
            'rpc call with named parameters #0' => [
                '{"jsonrpc": "2.0", "method": "subtract", "params": {"subtrahend": 23, "minuend": 42}, "id": 3}',
                'subtract',
                ['subtrahend' => 23, 'minuend' => 42],
                3,
            ],
            'rpc call with named parameters #1' => [
                '{"jsonrpc": "2.0", "method": "subtract", "params": {"minuend": 42, "subtrahend": 23}, "id": 4}',
                'subtract',
                ['minuend' => 42, 'subtrahend' => 23],
                4,
            ],
        ];
    }

    /**
     *  @see https://www.jsonrpc.org/specification#examples
     */
    public function providePassingSampleNotification(): array
    {
        return [
            'a Notification #0' => [
                '{"jsonrpc": "2.0", "method": "update", "params": [1,2,3,4,5]}',
                'update',
                [1, 2, 3, 4, 5],
            ],
            'a Notification #1' => [
                '{"jsonrpc": "2.0", "method": "foobar"}',
                'foobar',
                [],
            ],
        ];
    }

    /**
     *  @see https://de.wikipedia.org/wiki/JSON-RPC
     */
    public function provideValidNotifications(): array
    {
        return [
            [
                '{"method": "empfangeNachricht", "params": ["Benutzer1", "Wir unterhielten uns gerade"], "id": null}',
                'empfangeNachricht',
                ['Benutzer1', 'Wir unterhielten uns gerade'],
            ],
            [
                '{"method": "empfangeNachricht", "params": ["Benutzer3", "Ich muss jetzt los, tschüss"], "id": null}',
                'empfangeNachricht',
                ['Benutzer3', 'Ich muss jetzt los, tschüss'],
            ],
            [
                '{"method": "ändereStatus", "params": ["abwesend","Benutzer3"], "id": null}',
                'ändereStatus',
                ['abwesend', 'Benutzer3'],
            ],
        ];
    }

    public function provideErrorResponses(): array
    {
        return [
            'rpc call of non-existent method' => [
                [
                    new ErrorResponse(
                        '1',
                        new RpcException('Method not found', RpcException::METHOD_NOT_FOUND)
                    ),
                ],
                '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":"1"}',
            ],
            'rpc call with invalid JSON' => [
                [
                    new ErrorResponse(
                        null,
                        new RpcException('Parse error', RpcException::PARSE_ERROR)
                    ),
                ],
                '{"jsonrpc":"2.0","error":{"code":-32700,"message":"Parse error"},"id":null}',
            ],
            'rpc call with invalid Request object' => [
                [
                    new ErrorResponse(
                        null,
                        new RpcException('Invalid Request', RpcException::INVALID_REQUEST)
                    ),
                ],
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null}',
            ],
            'rpc call Batch, invalid JSON' => [
                [
                    new ErrorResponse(
                        null,
                        new RpcException('Parse error', RpcException::PARSE_ERROR)
                    ),
                ],
                '{"jsonrpc":"2.0","error":{"code":-32700,"message":"Parse error"},"id":null}',
            ],
            'rpc call with an empty Array' => [
                [
                    new ErrorResponse(
                        null,
                        new RpcException('Invalid Request', RpcException::INVALID_REQUEST)
                    ),
                ],
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null}',
            ],
            'rpc call with an invalid Batch (but not empty)' => [
                [
                    new ErrorResponse(
                        null,
                        new RpcException('Invalid Request', RpcException::INVALID_REQUEST)
                    ),
                ],
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null}',
            ],
            'rpc call with invalid Batch' => [
                [
                    new ErrorResponse(
                        null,
                        new RpcException('Invalid Request', RpcException::INVALID_REQUEST)
                    ),
                    new ErrorResponse(
                        null,
                        new RpcException('Invalid Request', RpcException::INVALID_REQUEST)
                    ),
                    new ErrorResponse(
                        null,
                        new RpcException('Invalid Request', RpcException::INVALID_REQUEST)
                    ),
                ],
                '[{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null},{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null},{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null}]',
            ],
        ];
    }

    public function provideResultResponses(): array
    {
        return [
            'rpc call with positional parameters #0' => [
                [
                    new ResultResponse(1, new class() implements JsonSerializable {
                        public function jsonSerialize()
                        {
                            return 19;
                        }
                    }),
                ],
                '{"jsonrpc":"2.0","result":19,"id":1}',
            ],
            'rpc call with positional parameters #1' => [
                [
                    new ResultResponse(2, new class() implements JsonSerializable {
                        public function jsonSerialize()
                        {
                            return -19;
                        }
                    }),
                ],
                '{"jsonrpc":"2.0","result":-19,"id":2}',
            ],
            'rpc call with named parameters #0' => [
                [
                    new ResultResponse(3, new class() implements JsonSerializable {
                        public function jsonSerialize()
                        {
                            return 19;
                        }
                    }),
                ],
                '{"jsonrpc":"2.0","result":19,"id":3}',
            ],
            'rpc call with named parameters #1' => [
                [
                    new ResultResponse(4, new class() implements JsonSerializable {
                        public function jsonSerialize()
                        {
                            return -19;
                        }
                    }),
                ],
                '{"jsonrpc":"2.0","result":-19,"id":4}',
            ],
        ];
    }

    public function provideMixedResponses(): array
    {
        return [
            'rpc call Batch' => [
                [
                    new ResultResponse('1', new class() implements JsonSerializable {
                        public function jsonSerialize()
                        {
                            return 7;
                        }
                    }),
                    new ResultResponse('2', new class() implements JsonSerializable {
                        public function jsonSerialize()
                        {
                            return 19;
                        }
                    }),
                    new ErrorResponse(
                        null,
                        new RpcException('Invalid Request', RpcException::INVALID_REQUEST)
                    ),
                    new ErrorResponse(
                        '5',
                        new RpcException('Method not found', RpcException::METHOD_NOT_FOUND)
                    ),
                    new ResultResponse('9', new class() implements JsonSerializable {
                        public function jsonSerialize()
                        {
                            return ['hello', 5];
                        }
                    }),
                ],
                '[{"jsonrpc":"2.0","result":7,"id":"1"},{"jsonrpc":"2.0","result":19,"id":"2"},{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null},{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":"5"},{"jsonrpc":"2.0","result":["hello",5],"id":"9"}]',
            ],
            'rpc call Batch (all notifications)' => [
                [],
                '',
            ],
        ];
    }
}
