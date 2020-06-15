<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use F8\Rpc\Interfaces\SerializerInterface;
use JsonException;
use stdClass;

class JsonSerializer implements SerializerInterface
{
    public static function deserializeCalls(
        string $body,
        int $paramsDepth = 0,
        int $jsonOptions = 0
    ) {
        /** @var array<stdClass|RpcException> */
        $calls = [];

        try {
            /** @var array|mixed|stdClass $out */
            $out = json_decode($body, false, 4 + $paramsDepth, JSON_THROW_ON_ERROR | $jsonOptions);
        } catch (JsonException $e) {
            switch ($e->getCode()) {
                case JSON_ERROR_DEPTH:
                throw new RpcException('Invalid Request: Exceeded maximum parameter depth.', RpcException::INVALID_REQUEST, $e);
                case JSON_ERROR_UTF8:
                case JSON_ERROR_UTF16:
                throw new RpcException('Error decoding JSON: Unsupported character for encoding.', RpcException::PARSE_CHARACTER_ENCODING_ERROR, $e);
                default:
                throw new RpcException('Error decoding JSON.', RpcException::PARSE_ERROR, $e);
            }
        }

        /** @var array<Notification|Request|RpcException> */
        $serializedCalls = [];

        if ($out instanceof stdClass) {
            $calls = [$out];
        } elseif (\is_array($out)) {
            /** @var mixed|stdClass $call */
            foreach ($out as $call) {
                if ($call instanceof stdClass) {
                    array_push($calls, $call);
                } else {
                    array_push($calls, new RpcException('Invalid Request', RpcException::INVALID_REQUEST));
                }
            }
        } else {
            throw new RpcException('Invalid Request: Expected Request object or array of Request objects.', RpcException::INVALID_REQUEST);
        }

        if (empty($calls)) {
            throw new RpcException('Invalid Request: Expected at least one valid Request or Notification.', RpcException::INVALID_REQUEST);
        }

        foreach ($calls as $call) {
            try {
                if ($call instanceof stdClass) {
                    $serializedCalls[] = static::deserializeCall($call);
                } elseif ($call instanceof RpcException) {
                    $serializedCalls[] = $call;
                } else {
                    $serializedCalls[] = new RpcException('Invalid Request', RpcException::INVALID_REQUEST);
                }
            } catch (RpcException $e) {
                $serializedCalls[] = $e;
            }
        }

        return \count($serializedCalls) > 1
            ? new BatchRequest($serializedCalls)
            : $serializedCalls[0];
    }

    public static function deserializeCall(stdClass $call)
    {
        if (!isset($call->id)) {
            $isNotification = true;
            $id = null;
            $presumedVersion = 2.0;
        } elseif (null === $call->id) {
            $isNotification = true;
            $id = null;
            $presumedVersion = 1.0;
        } elseif (\is_string($call->id)) {
            $isNotification = false;
            $id = (string) $call->id;
            $presumedVersion = null;
        } elseif (\is_int($call->id)) {
            $isNotification = false;
            $id = (int) $call->id;
            $presumedVersion = null;
        } else {
            throw new RpcException('Invalid Request: Expected member "id" to be NULL, a string, an integer or not defined.', RpcException::INVALID_REQUEST);
        }

        if (!isset($call->jsonrpc)) {
            $version = 1.0;
        } elseif ('2.0' === $call->jsonrpc) {
            $version = 2.0;
            if (1.0 === $presumedVersion) {
                $isNotification = false;
            }
        } else {
            throw new RpcException('Invalid Request: Expected JSON-RPC Version 1.0 or 2.0.', RpcException::INVALID_REQUEST, null, $id);
        }

        if (!isset($call->method)) {
            throw new RpcException('Invalid Request: Missing required member "method".', RpcException::INVALID_REQUEST, null, $id);
        }
        if (!\is_string($call->method)) {
            throw new RpcException('Invalid Request: Expected member "method" to be string.', RpcException::INVALID_REQUEST, null, $id);
        }
        $method = (string) $call->method;

        if (!isset($call->params)) {
            $params = [];
        } elseif (\is_array($call->params)) {
            $params = (array) $call->params;
        } elseif ($call->params instanceof stdClass) {
            if (1.0 === $version) {
                throw new RpcException('Invalid Request: Expected member "params" to be array.', RpcException::INVALID_REQUEST, null, $id);
            }
            $params = (array) $call->params;
        } else {
            throw new RpcException('Invalid Request: Expected member "params" to be array, object or not defined.', RpcException::INVALID_REQUEST, null, $id);
        }

        $call = (array) $call;
        unset($call['id'], $call['jsonrpc'], $call['method'], $call['params']);

        if (!empty($call)) {
            throw new RpcException('Invalid Request: Unexpected members.', RpcException::INVALID_REQUEST, null, $id);
        }

        return $isNotification
            ? new Notification($method, $params)
            : new Request($id, $method, $params);
    }

    public static function serializeResponses(array $responses): string
    {
        try {
            if (\count($responses) > 1) {
                // Batch Call

                $json = json_encode($responses, JSON_THROW_ON_ERROR);
            } elseif (1 === \count($responses)) {
                // Single Call
                // If the (Batch) call is invalid JSON, the Server MUST respond with a single Response Object.
                // If the Batch call contains only one response, the Server MUST respond with a single Response Object.

                $json = json_encode($responses[0], JSON_THROW_ON_ERROR);
            } else {
                // No Responses
                // If there are no Response objects, the Server MUST NOT send an empty Array, but return NOTHING.

                $json = '';
            }
        } catch (JsonException $e) {
            // -32099: Implementation-defined server error
            // This uses a constant string, because there may be some unexpected issue with json_encode.

            $json = '{"jsonrpc":"2.0","id":null,"error":{"code":-32099,"message":"Error serializing response."}}';
        }

        return $json;
    }
}
