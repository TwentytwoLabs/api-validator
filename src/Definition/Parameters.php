<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Definition;

/**
 * Class Parameters.
 */
class Parameters implements \Serializable, \IteratorAggregate
{
    /**
     * @var Parameter[]
     */
    private array $parameters = [];

    public function __construct(array $parameters)
    {
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->parameters as $name => $parameter) {
            yield $name => $parameter;
        }
    }

    public function hasBodySchema(): bool
    {
        $body = $this->getBody();

        return null !== $body && $body->hasSchema();
    }

    public function getBodySchema(): ?\stdClass
    {
        return null === $this->getBody() ? null : $this->getBody()->getSchema();
    }

    public function hasPathSchema(): bool
    {
        return null !== $this->getPathSchema();
    }

    public function getPathSchema(): ?\stdClass
    {
        return $this->getSchema($this->getPath());
    }

    public function hasQueryParametersSchema(): bool
    {
        return null !== $this->getQueryParametersSchema();
    }

    /**
     * JSON Schema for the query parameters.
     */
    public function getQueryParametersSchema(): ?\stdClass
    {
        return $this->getSchema($this->getQuery());
    }

    public function hasHeadersSchema(): bool
    {
        return null !== $this->getHeadersSchema();
    }

    /**
     * JSON Schema for the headers.
     */
    public function getHeadersSchema(): ?\stdClass
    {
        return $this->getSchema($this->getHeaders());
    }

    /**
     * @return Parameter[]
     */
    public function getPath(): array
    {
        return $this->findByLocation('path');
    }

    /**
     * @return Parameter[]
     */
    public function getQuery(): array
    {
        return $this->findByLocation('query');
    }

    /**
     * @return Parameter[]
     */
    public function getHeaders(): array
    {
        return $this->findByLocation('header');
    }

    public function getBody(): ?Parameter
    {
        $match = $this->findByLocation('body');
        if (empty($match)) {
            return null;
        }

        return current($match);
    }

    public function getByName(string $name): ?Parameter
    {
        if (!isset($this->parameters[$name])) {
            return null;
        }

        return $this->parameters[$name];
    }

    // Serializable
    public function __serialize(): array
    {
        return ['parameters' => $this->parameters];
    }

    public function __unserialize(array $data): void
    {
        $this->parameters = $data['parameters'];
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

    /**
     * @param Parameter[] $parameters
     */
    private function getSchema(array $parameters): ?\stdClass
    {
        if (empty($parameters)) {
            return null;
        }

        $schema = new \stdClass();
        $schema->type = 'object';
        $schema->required = [];
        $schema->properties = new \stdClass();
        foreach ($parameters as $name => $parameter) {
            if ($parameter->isRequired()) {
                $schema->required[] = $parameter->getName();
            }
            $schema->properties->{$name} = $parameter->getSchema();
        }

        return $schema;
    }

    /**
     * @return Parameter[]
     */
    private function findByLocation(string $location): array
    {
        return array_filter(
            $this->parameters,
            function (Parameter $parameter) use ($location) {
                return $parameter->getLocation() === $location;
            }
        );
    }

    private function addParameter(Parameter $parameter)
    {
        $this->parameters[$parameter->getName()] = $parameter;
    }
}
