<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator;

use Rize\UriTemplate;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinitions;

class Schema
{
    private OperationDefinitions $operationDefinitions;

    public function __construct(OperationDefinitions $operationDefinitions)
    {
        $this->operationDefinitions = $operationDefinitions;
    }

    public function getOperationDefinitions(): OperationDefinitions
    {
        return $this->operationDefinitions;
    }

    public function getOperationDefinition(
        string $operationId = '',
        string $method = '',
        string $path = ''
    ): OperationDefinition {
        if (!empty($operationId)) {
            return $this->operationDefinitions->getOperationDefinition($operationId);
        }

        return $this->findOperation($method, $path);
    }

    private function findOperation(string $method, string $path): OperationDefinition
    {
        foreach ($this->operationDefinitions as $requestDefinition) {
            if ($requestDefinition->getMethod() !== $method) {
                continue;
            }

            if ($this->isMatchingPath($requestDefinition->getPathTemplate(), $path)) {
                return $requestDefinition;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unable to resolve the operationId for path %s', $path));
    }

    private function isMatchingPath(string $pathTemplate, string $requestPath): bool
    {
        if ($pathTemplate === $requestPath) {
            return true;
        }

        return null !== (new UriTemplate())->extract($pathTemplate, $requestPath, true);
    }
}
