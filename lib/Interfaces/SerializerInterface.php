<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc\Interfaces;

use F8\Rpc\AbstractResponse;
use F8\Rpc\BatchRequest;
use F8\Rpc\Notification;
use F8\Rpc\Request;

interface SerializerInterface
{
    /**
     * @return BatchRequest|Notification|Request
     */
    public static function deserializeCalls(
        string $body,
        int $paramsDepth = 0,
        int $jsonOptions = 0
    );

    /**
     * @param array<AbstractResponse> $responses
     */
    public static function serializeResponses(array $responses): string;
}
