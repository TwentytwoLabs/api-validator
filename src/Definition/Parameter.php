<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Definition;

/**
 * Class Parameter.
 */
class Parameter implements \Serializable
{
    public const BODY_LOCATIONS = ['formData', 'body'];
    public const BODY_LOCATIONS_TYPES = ['formData' => 'application/x-www-form-urlencoded', 'body' => 'application/json'];

    private const LOCATIONS = ['path', 'header', 'query', 'body', 'formData'];

    private string $location;
    private string $name;
    private bool $required;
    private ?\stdClass $schema;

    public function __construct(string $location, string $name, bool $required = false, ?\stdClass $schema = null)
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

    public function getSchema(): ?\stdClass
    {
        return $this->schema;
    }

    public function hasSchema(): bool
    {
        return null !== $this->schema;
    }

    public function serialize(): string
    {
        return serialize([
            'location' => $this->location,
            'name' => $this->name,
            'required' => $this->required,
            'schema' => $this->schema,
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->location = $data['location'];
        $this->name = $data['name'];
        $this->required = $data['required'];
        $this->schema = $data['schema'];
    }
}
