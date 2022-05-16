<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Decoder;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Decoder\DecoderUtils;

/**
 * Class DecoderUtilsTest.
 */
class DecoderUtilsTest extends TestCase
{
    /**
     * @dataProvider dataForExtractFormatFromContentType
     */
    public function testExtractFormatFromContentType(string $contentType, string $format)
    {
        $this->assertSame($format, DecoderUtils::extractFormatFromContentType($contentType));
    }

    public function dataForExtractFormatFromContentType(): array
    {
        return [
            ['text/plain', 'plain'],
            ['application/xhtml+xml', 'xhtml+xml'],
            ['application/hal+json; charset=utf-8', 'hal+json'],
        ];
    }
}
