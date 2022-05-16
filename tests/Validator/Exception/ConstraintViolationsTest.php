<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Validator\Exception;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Validator\ConstraintViolation;
use TwentytwoLabs\Api\Validator\Exception\ConstraintViolations;

/**
 * Class ConstraintViolationsTest.
 */
class ConstraintViolationsTest extends TestCase
{
    public function testShouldNotAddViolation()
    {
        $exception = new ConstraintViolations([]);

        $this->assertEmpty($exception->getViolations());
        $this->assertSame("Request constraint violations:\n", $exception->__toString());
    }

    public function testShouldAddViolation()
    {
        /** @var ConstraintViolation|MockObject $violation */
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->expects($this->exactly(2))->method('getLocation')->willReturn('header');
        $violation->expects($this->exactly(2))->method('getMessage')->willReturn('foo is required');
        $violation->expects($this->exactly(2))->method('getConstraint')->willReturn('enum');
        $violation->expects($this->exactly(2))->method('getProperty')->willReturn('foo');

        $exception = new ConstraintViolations([$violation]);
        $this->assertNotEmpty($exception->getViolations());
        $this->assertCount(1, $exception->getViolations());
        $this->assertSame("Request constraint violations:\n[property]: foo\n[message]: foo is required\n[constraint]: enum\n[location]: header\n\n", $exception->__toString());
    }
}
