<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Definition;

class OperationDefinitions implements \IteratorAggregate
{
    /**
     * @var OperationDefinition[]
     */
    private array $definitions = [];

    public function __construct(array $requestDefinitions = [])
    {
        foreach ($requestDefinitions as $requestDefinition) {
            $this->addRequestDefinition($requestDefinition);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getOperationDefinition(string $operationId): OperationDefinition
    {
        if (isset($this->definitions[$operationId])) {
            return $this->definitions[$operationId];
        }

        throw new \InvalidArgumentException(sprintf(
            'Unable to find request definition for operationId %s',
            $operationId
        ));
    }

    // IteratorAggregate

    public function getIterator(): \Traversable
    {
        foreach ($this->definitions as $operationId => $requestDefinition) {
            yield $operationId => $requestDefinition;
        }
    }

    private function addRequestDefinition(OperationDefinition $requestDefinition): void
    {
        $this->definitions[$requestDefinition->getOperationId()] = $requestDefinition;
    }
}
