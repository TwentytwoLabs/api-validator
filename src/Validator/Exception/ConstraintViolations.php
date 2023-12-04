<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Validator\Exception;

use TwentytwoLabs\ApiValidator\Validator\ConstraintViolation;

final class ConstraintViolations extends \Exception
{
    /**
     * @var ConstraintViolation[]
     */
    private array $violations;

    /**
     * @param ConstraintViolation[] $violations
     */
    public function __construct(array $violations)
    {
        $this->violations = $violations;
        parent::__construct((string) $this);
    }

    /**
     * @return ConstraintViolation[]
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    public function __toString(): string
    {
        $message = "Request constraint violations:\n";

        foreach ($this->violations as $violation) {
            $message .= sprintf(
                "[property]: %s\n[message]: %s\n[constraint]: %s\n[location]: %s\n\n",
                $violation->getProperty(),
                $violation->getMessage(),
                $violation->getConstraint(),
                $violation->getLocation()
            );
        }

        return $message;
    }
}
