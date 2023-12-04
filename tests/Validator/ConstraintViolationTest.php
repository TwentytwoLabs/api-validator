<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Validator;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Validator\ConstraintViolation;

final class ConstraintViolationTest extends TestCase
{
    public function testConstraintViolationToArray()
    {
        $expectedArray = [
            'property' => 'property_one',
            'message' => 'a violation message',
            'constraint' => 'required',
            'location' => 'query',
        ];

        $violation = new ConstraintViolation('property_one', 'a violation message', 'required', 'query');

        $this->assertSame('property_one', $violation->getProperty());
        $this->assertSame('a violation message', $violation->getMessage());
        $this->assertSame('required', $violation->getConstraint());
        $this->assertSame('query', $violation->getLocation());
        $this->assertSame($expectedArray, $violation->toArray());
    }
}
