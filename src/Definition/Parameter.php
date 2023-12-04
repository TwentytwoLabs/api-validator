<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Definition;

final class Parameter
{
    private const LOCATIONS = ['header', 'path', 'query', 'body', 'formData'];

    private string $location;
    private string $name;
    private bool $required;
    private array $schema;

    public function __construct(string $location, string $name, bool $required = false, array $schema = [])
    {
        if (!\in_array($location, self::LOCATIONS, true)) {
            throw new \InvalidArgumentException(sprintf('%s is not a supported parameter location, supported: %s', $location, implode(', ', self::LOCATIONS)));
        }

        $this->location = $location;
        $this->name = $name;
        $this->required = $required;
        $this->schema = $schema;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function hasSchema(): bool
    {
        return !empty($this->schema);
    }
}
