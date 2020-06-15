<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use F8\IO\Interfaces\ContextFactoryInterface;
use F8\Rpc\Interfaces\RouterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractRequestHandler implements RequestHandlerInterface
{
    protected RouterInterface $router;

    protected ContextFactoryInterface $contextFactory;

    protected ResponseFactoryInterface $responseFactory;

    public function __construct(
        RouterInterface $router,
        ContextFactoryInterface $contextFactory,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->router = $router;
        $this->contextFactory = $contextFactory;
        $this->responseFactory = $responseFactory;
    }

    abstract public function handle(ServerRequestInterface $request): ResponseInterface;

    abstract public function handleCall(Notification $call): ?AbstractResponse;

    public function createResponse(string $body, string $contentType): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200, 'OK');

        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', $contentType);
    }
}
