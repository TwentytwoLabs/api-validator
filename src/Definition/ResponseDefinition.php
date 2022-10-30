<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Definition;

/**
 * Class ResponseDefinition.
 */
class ResponseDefinition implements \Serializable, MessageDefinition
{
    /**
     * @var int|string
     */
    private string|int $statusCode;
    private array $contentTypes;
    private Parameters $parameters;

    /**
     * @param string[]   $allowedContentTypes
     */
    public function __construct(int|string $statusCode, array $allowedContentTypes, Parameters $parameters)
    {
        $this->statusCode = $statusCode;
        $this->contentTypes = $allowedContentTypes;
        $this->parameters = $parameters;
    }

    public function getStatusCode(): int|string
    {
        return $this->statusCode;
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
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

    /**
     * Supported response types.
     *
     * @return string[]
     */
    public function getContentTypes(): array
    {
        return $this->contentTypes;
    }

    // Serializable
    public function __serialize(): array
    {
        return [
            'statusCode' => $this->statusCode,
            'contentTypes' => $this->contentTypes,
            'parameters' => $this->parameters,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->statusCode = $data['statusCode'];
        $this->contentTypes = $data['contentTypes'];
        $this->parameters = $data['parameters'];
    }

    public function serialize(): string
    {
        return serialize($this->serialize());
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->__unserialize(unserialize($serialized));
    }
}
