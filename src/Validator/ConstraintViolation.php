<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Validator;

/**
 * ValueObject that contains constraint violation properties.
 *
 * Class ConstraintViolation.
 */
class ConstraintViolation
{
    private string $property;
    private string $message;
    private string $constraint;
    private string $location;

    public function __construct(string $property, string $message, string $constraint, string $location)
    {
        $this->property = $property;
        $this->message = $message;
        $this->constraint = $constraint;
        $this->location = $location;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getConstraint(): string
    {
        return $this->constraint;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function toArray(): array
    {
        return [
            'property' => $this->getProperty(),
            'message' => $this->getMessage(),
            'constraint' => $this->getConstraint(),
            'location' => $this->getLocation(),
        ];
    }
}
