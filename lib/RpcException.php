<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use Exception;
use JsonSerializable;
use Throwable;

class RpcException extends Exception implements JsonSerializable
{
    /**
     * Invalid data was received by the server.
     * An error occurred on the server while parsing the data.
     */
    public const PARSE_ERROR = -32700;

    /**
     * Parse error: Unsupported encoding.
     */
    public const PARSE_ENCODING_ERROR = -32701;

    /**
     * Parse error: Unsupported character for encoding.
     */
    public const PARSE_CHARACTER_ENCODING_ERROR = -32702;

    /**
     * The data sent is not a valid Request object.
     */
    public const INVALID_REQUEST = -32600;

    /**
     * The method does not exist / is not available.
     */
    public const METHOD_NOT_FOUND = -32601;

    /**
     * Invalid method parameter(s).
     */
    public const INVALID_PARAMS = -32602;

    /**
     * Internal RPC error.
     */
    public const INTERNAL_ERROR = -32603;

    /**
     * Application Error (if not already defined outside of -32768..-32000).
     */
    public const APPLICATION_ERROR = -32500;

    /**
     * System Error.
     */
    public const SYSTEM_ERROR = -32400;

    /**
     * System Error.
     */
    public const TRANSPORT_ERROR = -32300;

    /**
     * @var null|int|string
     */
    protected $callId;

    /**
     * @param null|int|string $callId
     */
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null, $callId = null)
    {
        $this->callId = $callId;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return null|int|string
     */
    public function getCallId()
    {
        return $this->callId;
    }

    public function jsonSerialize()
    {
        return [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $this->getCode(),
                'message' => $this->getMessage(),
            ],
            'id' => $this->getCallId(),
        ];
    }
}
