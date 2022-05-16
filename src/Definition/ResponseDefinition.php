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
    private $statusCode;
    private array $contentTypes;
    private Parameters $parameters;

    /**
     * @param int|string $statusCode
     * @param string[]   $allowedContentTypes
     */
    public function __construct($statusCode, array $allowedContentTypes, Parameters $parameters)
    {
        $this->statusCode = $statusCode;
        $this->contentTypes = $allowedContentTypes;
        $this->parameters = $parameters;
    }

    /**
     * @return int|string
     */
    public function getStatusCode()
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

    public function serialize(): string
    {
        return serialize([
            'statusCode' => $this->statusCode,
            'contentTypes' => $this->contentTypes,
            'parameters' => $this->parameters,
        ]);
    }

    // Serializable
    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->statusCode = $data['statusCode'];
        $this->contentTypes = $data['contentTypes'];
        $this->parameters = $data['parameters'];
    }
}
