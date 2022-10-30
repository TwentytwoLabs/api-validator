<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Definition;

class RequestDefinition implements \Serializable, MessageDefinition
{
    private string $method;
    private string $operationId;
    private string $pathTemplate;
    private Parameters $parameters;
    private array $contentTypes;
    private array $accepts;
    private array $responses = [];

    /**
     * @param string[]             $contentTypes
     * @param ResponseDefinition[] $responses
     */
    public function __construct(
        string $method,
        string $operationId,
        string $pathTemplate,
        Parameters $parameters,
        array $contentTypes,
        array $accepts,
        array $responses
    ) {
        $this->method = $method;
        $this->operationId = $operationId;
        $this->pathTemplate = $pathTemplate;
        $this->parameters = $parameters;
        $this->contentTypes = $contentTypes;
        $this->accepts = $accepts;
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
        return $this->contentTypes;
    }

    public function getAccepts(): array
    {
        return $this->accepts;
    }

    public function getResponseDefinition(int|string $statusCode): ResponseDefinition
    {
        if (isset($this->responses[$statusCode])) {
            return $this->responses[$statusCode];
        }

        if (isset($this->responses['default'])) {
            return $this->responses['default'];
        }

        throw new \InvalidArgumentException(sprintf('No response definition for %s %s is available for status code %s', $this->method, $this->pathTemplate, $statusCode));
    }

    public function hasBodySchema(): bool
    {
        return $this->parameters->hasBodySchema();
    }

    public function getBodySchema(): ?\stdClass
    {
        return $this->parameters->getBodySchema();
    }

    public function hasHeadersSchema(): bool
    {
        return $this->parameters->hasHeadersSchema();
    }

    public function getHeadersSchema(): ?\stdClass
    {
        return $this->parameters->getHeadersSchema();
    }

    public function hasPathSchema(): bool
    {
        return $this->parameters->hasPathSchema();
    }

    public function getPathSchema(): ?\stdClass
    {
        return $this->parameters->getPathSchema();
    }

    public function hasQueryParametersSchema(): bool
    {
        return $this->parameters->hasQueryParametersSchema();
    }

    public function getQueryParametersSchema(): ?\stdClass
    {
        return $this->parameters->getQueryParametersSchema();
    }

    // Serializable
    public function __serialize(): array
    {
        return [
            'method' => $this->method,
            'operationId' => $this->operationId,
            'pathTemplate' => $this->pathTemplate,
            'parameters' => $this->parameters,
            'contentTypes' => $this->contentTypes,
            'accepts' => $this->accepts,
            'responses' => $this->responses,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->method = $data['method'];
        $this->operationId = $data['operationId'];
        $this->pathTemplate = $data['pathTemplate'];
        $this->parameters = $data['parameters'];
        $this->contentTypes = $data['contentTypes'];
        $this->accepts = $data['accepts'];
        $this->responses = $data['responses'];
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->__unserialize(unserialize($serialized));
    }

    private function addResponseDefinition(ResponseDefinition $response)
    {
        $this->responses[$response->getStatusCode()] = $response;
    }
}
