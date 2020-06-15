<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use F8\IO\Interfaces\GuardDispatcherInterface;
use F8\Rpc\Interfaces\RouterInterface;
use RuntimeException;

class BasicRouter implements RouterInterface
{
    /**
     * @var array<string,GuardDispatcherInterface>
     */
    protected array $map;

    /**
     * @param array<string,GuardDispatcherInterface> $map
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    public function add(string $method, GuardDispatcherInterface $taskHandler): void
    {
        if (isset($this->map[$method])) {
            throw new RpcException(
                'Duplicate method declaration',
                RpcException::APPLICATION_ERROR,
                new RuntimeException('Declared method already present in map')
            );
        }

        $this->map[$method] = $taskHandler;
    }

    public function run(string $method): GuardDispatcherInterface
    {
        if (!isset($this->map[$method])) {
            throw new RpcException('Method not found', RpcException::METHOD_NOT_FOUND);
        }

        return $this->map[$method];
    }
}
