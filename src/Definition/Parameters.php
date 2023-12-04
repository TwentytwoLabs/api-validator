<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Definition;

final class Parameters implements \IteratorAggregate
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

    public function hasHeadersSchema(): bool
    {
        return !empty($this->getHeadersSchema());
    }

    public function getHeadersSchema(): array
    {
        return $this->getSchema($this->getHeaders());
    }

    public function hasPathSchema(): bool
    {
        return !empty($this->getPathSchema());
    }

    public function getPathSchema(): array
    {
        return $this->getSchema($this->getPath());
    }

    public function hasQueryParametersSchema(): bool
    {
        return !empty($this->getQueryParametersSchema());
    }

    public function getQueryParametersSchema(): array
    {
        return $this->getSchema($this->getQuery());
    }

    public function hasBodySchema(): bool
    {
        $body = $this->getBody();
        if (null === $body) {
            return false;
        }

        return $body->hasSchema();
    }

    public function getBodySchema(): array
    {
        $body = $this->getBody();
        if (null === $body) {
            return [];
        }

        return $body->getSchema();
    }

    /**
     * @return Parameter[]
     */
    public function getHeaders(): array
    {
        return $this->findByLocation('header');
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

    public function getBody(): ?Parameter
    {
        $match = $this->findByLocation('body');

        return empty($match) ? null : current($match);
    }

    public function getByName(string $name): ?Parameter
    {
        return $this->parameters[$name] ?? null;
    }

    public function addParameter(Parameter $parameter): void
    {
        $this->parameters[$parameter->getName()] = $parameter;
    }

    /**
     * @param Parameter[] $parameters
     */
    private function getSchema(array $parameters): array
    {
        if (empty($parameters)) {
            return [];
        }

        $schema = ['type' => 'object', 'required' => [], 'properties' => []];
        foreach ($parameters as $name => $parameter) {
            if ($parameter->isRequired()) {
                $schema['required'][] = $parameter->getName();
            }

            $schema['properties'][$name] = $parameter->getSchema();
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
}
