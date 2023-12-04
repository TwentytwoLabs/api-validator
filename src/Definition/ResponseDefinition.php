<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Definition;

class ResponseDefinition implements MessageDefinition
{
    private string|int $statusCode;
    private Parameters $parameters;

    public function __construct(int|string $statusCode, Parameters $parameters)
    {
        $this->statusCode = $statusCode;
        $this->parameters = $parameters;
    }

    public function getStatusCode(): int|string
    {
        return $this->statusCode;
    }

    public function hasHeadersSchema(): bool
    {
        return $this->parameters->hasHeadersSchema();
    }

    public function getHeadersSchema(): array
    {
        return $this->parameters->getHeadersSchema();
    }

    public function getContentTypes(): array
    {
        return array_keys($this->getBodySchema());
    }

    public function hasBodySchema(): bool
    {
        return $this->parameters->hasBodySchema();
    }

    public function getBodySchema(): array
    {
        return $this->parameters->getBodySchema();
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
    }
}
