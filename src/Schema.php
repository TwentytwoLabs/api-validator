<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api;

use Rize\UriTemplate;
use TwentytwoLabs\Api\Definition\RequestDefinition;
use TwentytwoLabs\Api\Definition\RequestDefinitions;

/**
 * Class Schema.
 */
class Schema implements \Serializable
{
    private RequestDefinitions $requestDefinitions;
    private string $host;
    private string $basePath;
    private array $schemes;

    public function __construct(
        RequestDefinitions $requestDefinitions,
        string $basePath = '',
        string $host = '',
        array $schemes = ['http']
    ) {
        $this->requestDefinitions = $requestDefinitions;
        $this->host = $host;
        $this->basePath = $basePath;
        $this->schemes = $schemes;
    }

    /**
     * Find the operationId associated to a given path and method.
     *
     * @throws \InvalidArgumentException
     */
    public function findOperationId(string $method, string $path): string
    {
        foreach ($this->requestDefinitions as $requestDefinition) {
            if ($requestDefinition->getMethod() !== $method) {
                continue;
            }

            if ($this->isMatchingPath($requestDefinition->getPathTemplate(), $path)) {
                return $requestDefinition->getOperationId();
            }
        }

        throw new \InvalidArgumentException(sprintf('Unable to resolve the operationId for path %s', $path));
    }

    public function getRequestDefinitions(): RequestDefinitions
    {
        return $this->requestDefinitions;
    }

    public function getRequestDefinition(string $operationId): RequestDefinition
    {
        return $this->requestDefinitions->getRequestDefinition($operationId);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @return string[]
     */
    public function getSchemes(): array
    {
        return $this->schemes;
    }

    // Serializable

    public function serialize(): string
    {
        return serialize([
            'host' => $this->host,
            'basePath' => $this->basePath,
            'schemes' => $this->schemes,
            'requests' => $this->requestDefinitions,
        ]);
    }

    // Serializable
    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->host = $data['host'];
        $this->basePath = $data['basePath'];
        $this->schemes = $data['schemes'];
        $this->requestDefinitions = $data['requests'];
    }

    private function isMatchingPath(string $pathTemplate, string $requestPath): bool
    {
        if ($pathTemplate === $requestPath) {
            return true;
        }

        return null !== (new UriTemplate())->extract($pathTemplate, $requestPath, true);
    }
}
