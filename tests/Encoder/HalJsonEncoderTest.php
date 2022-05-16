<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Encoder;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Encoder\HalJsonEncoder;

/**
 * Class HalJsonEncoderTest.
 *
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class HalJsonEncoderTest extends TestCase
{
    public function testShouldSupportsEncoding()
    {
        $encoder = $this->getEncoder();

        $this->assertTrue($encoder->supportsEncoding('hal+json'));
    }

    /**
     * @dataProvider getBadFormat
     */
    public function testShouldNotSupportsEncoding(string $format)
    {
        $encoder = $this->getEncoder();

        $this->assertFalse($encoder->supportsEncoding($format));
    }

    public function testShouldSupportsDecoding()
    {
        $encoder = $this->getEncoder();

        $this->assertTrue($encoder->supportsDecoding('hal+json'));
    }

    /**
     * @dataProvider getBadFormat
     */
    public function testShouldNotSupportsDecoding(string $format)
    {
        $encoder = $this->getEncoder();

        $this->assertFalse($encoder->supportsDecoding($format));
    }

    public function getBadFormat(): array
    {
        return [
            ['xml'],
            ['json'],
            ['ld+json'],
        ];
    }

    private function getEncoder(): HalJsonEncoder
    {
        return new HalJsonEncoder();
    }
}
