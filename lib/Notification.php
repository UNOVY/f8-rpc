<?php

/**
 * @author Florian Gärber <florian@unovy.io>
 * @copyright 2020 Florian Gärber
 * @license MIT
 */

declare(strict_types=1);

namespace F8\Rpc;

use F8\IO\Interfaces\InputInterface;
use JsonSerializable;

class Notification implements JsonSerializable, InputInterface
{
    protected string $method;

    protected array $params;

    public function __construct(string $method, array $params)
    {
        $this->method = $method;
        $this->params = $params;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->params[$key] ?? null;
    }

    public function take(string $key)
    {
        /** @var mixed */
        $value = $this->get($key);

        unset($this->params[$key]);

        return $value;
    }

    public function has(string $key): bool
    {
        return isset($this->params[$key]);
    }

    public function empty(): bool
    {
        return \count(array_values($this->params)) > 0;
    }

    public function toArray(): array
    {
        return [
            'jsonrpc' => '2.0',
            'method' => $this->getMethod(),
            'params' => $this->getParams(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
