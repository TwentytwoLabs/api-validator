<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Encoder;

use TwentytwoLabs\Api\Encoder\ProblemJsonEncoder;
use PHPUnit\Framework\TestCase;

/**
 * Class ProblemJsonEncoderTest.
 *
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class ProblemJsonEncoderTest extends TestCase
{
    public function testShouldSupportsDecoding()
    {
        $encoder = $this->getEncoder();
        $this->assertTrue($encoder->supportsDecoding('problem+json'));
    }

    public function testShouldNotSupportsDecoding()
    {
        $encoder = $this->getEncoder();
        $this->assertFalse($encoder->supportsDecoding('json'));
    }

    private function getEncoder(): ProblemJsonEncoder
    {
        return new ProblemJsonEncoder();
    }
}
