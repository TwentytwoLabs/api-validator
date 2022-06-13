<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Definition;

/**
 * Class RequestDefinitions.
 */
class RequestDefinitions implements \Serializable, \IteratorAggregate
{
    /**
     * @var RequestDefinition[]
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
    public function getRequestDefinition(string $operationId): RequestDefinition
    {
        if (isset($this->definitions[$operationId])) {
            return $this->definitions[$operationId];
        }

        throw new \InvalidArgumentException(sprintf('Unable to find request definition for operationId %s', $operationId));
    }

    // IteratorAggregate

    public function getIterator(): \Traversable
    {
        foreach ($this->definitions as $operationId => $requestDefinition) {
            yield $operationId => $requestDefinition;
        }
    }

    // Serializable
    public function __serialize(): array
    {
        return ['definitions' => $this->definitions];
    }

    public function __unserialize(array $data): void
    {
        $this->definitions = $data['definitions'];
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize($serialized)
    {
        $this->__unserialize(unserialize($serialized));
    }

    private function addRequestDefinition(RequestDefinition $requestDefinition): void
    {
        $this->definitions[$requestDefinition->getOperationId()] = $requestDefinition;
    }
}
