<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Definition;

class OperationDefinition implements MessageDefinition
{
    private string $method;
    private string $operationId;
    private string $pathTemplate;
    private Parameters $parameters;
    private array $responses = [];

    public function __construct(
        string $method,
        string $operationId,
        string $pathTemplate,
        Parameters $parameters,
        array $responses
    ) {
        $this->method = $method;
        $this->operationId = $operationId;
        $this->pathTemplate = $pathTemplate;
        $this->parameters = $parameters;
        foreach ($responses as $response) {
            $this->addResponseDefinition($response);
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function getPathTemplate(): string
    {
        return $this->pathTemplate;
    }

    public function getRequestParameters(): Parameters
    {
        return $this->parameters;
    }

    public function getContentTypes(): array
    {
        $headers = $this->parameters->getHeaders();
        $header = $headers['content-type'] ?? null;
        if (null === $header) {
            return [];
        }

        return $header->getSchema()['enum'] ?? [];
    }

    public function getResponseDefinition(int|string $statusCode): ResponseDefinition
    {
        $response = $this->responses[$statusCode] ?? $this->responses['default'] ?? null;
        if (null === $response) {
            $message = sprintf('No response definition for %s %s is available for status code %s', $this->method, $this->pathTemplate, $statusCode);
            throw new \InvalidArgumentException($message);
        }

        return $response;
    }

    public function hasHeadersSchema(): bool
    {
        return $this->parameters->hasHeadersSchema();
    }

    public function getHeadersSchema(): array
    {
        return $this->parameters->getHeadersSchema();
    }

    public function hasPathSchema(): bool
    {
        return $this->parameters->hasPathSchema();
    }

    public function getPathSchema(): array
    {
        return $this->parameters->getPathSchema();
    }

    public function hasQueryParametersSchema(): bool
    {
        return $this->parameters->hasQueryParametersSchema();
    }

    public function getQueryParametersSchema(): array
    {
        return $this->parameters->getQueryParametersSchema();
    }

    public function hasBodySchema(): bool
    {
        return $this->parameters->hasBodySchema();
    }

    public function getBodySchema(): array
    {
        return $this->parameters->getBodySchema();
    }

    private function addResponseDefinition(ResponseDefinition $response): void
    {
        $this->responses[$response->getStatusCode()] = $response;
    }
}
