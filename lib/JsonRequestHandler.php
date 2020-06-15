<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class JsonRequestHandler extends AbstractRequestHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $calls = [];
        /** @var array<AbstractResponse> */
        $responses = [];

        try {
            $deserialized = JsonSerializer::deserializeCalls((string) $request->getBody(), 1);
        } catch (RpcException $e) {
            if ($e->getCode()) {
                // If the (Batch) call is invalid JSON, the Server MUST respond with a single Response Object.
                $responses[] = new ErrorResponse(null, $e);
            } elseif ($e->getCallId()) {
                $responses[] = new ErrorResponse($e->getCallId(), $e);
            }

            $deserialized = null;
        }

        if ($deserialized instanceof BatchRequest) {
            $calls = $deserialized->getCalls();
        } elseif ($deserialized instanceof Request || $deserialized instanceof Notification) {
            $calls = [$deserialized];
        }

        foreach ($calls as $call) {
            if ($call instanceof RpcException) {
                $responses[] = new ErrorResponse($call->getCallId(), $call);

                continue;
            }

            $response = $this->handleCall($call);

            if ($call instanceof Request && $response) {
                $responses[] = $response;
            }
        }

        if (empty($calls) && empty($responses)) {
            $responses[] = new ErrorResponse(null, new RpcException(
                'Parse Error: Expected call to contain Requests.',
                RpcException::PARSE_ERROR,
            ));
        }

        $json = JsonSerializer::serializeResponses($responses);

        return $this->createResponse($json, 'application/rpc+json');
    }

    /**
     * @todo Allow integration-level error handling/pre-processing?
     */
    public function handleCall(Notification $call): ?AbstractResponse
    {
        // false == isNotification
        $id = $call instanceof Request ? $call->getId() : false;

        $method = $call->getMethod();

        $context = $this->contextFactory->createContext();

        try {
            $taskHandler = $this->router->run($method);

            $output = $taskHandler->handle($context, $call);
        } catch (Throwable $t) {
            // Application SHOULD use a custom error handler to remove sensitive
            // information from error messages before sending them to the client.

            // if ($this->errorHandler) {
            //     $t = $this->errorHandler->handleException($t);
            // }

            return false === $id ? null : new ErrorResponse($id, $t);
        }

        return false === $id ? null : new ResultResponse($id, $output);
    }
}
