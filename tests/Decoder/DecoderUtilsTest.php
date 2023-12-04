<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Decoder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Decoder\DecoderUtils;

final class DecoderUtilsTest extends TestCase
{
    #[DataProvider('dataForExtractFormatFromContentType')]
    public function testExtractFormatFromContentType(string $contentType, string $format)
    {
        $this->assertSame($format, DecoderUtils::extractFormatFromContentType($contentType));
    }

    public static function dataForExtractFormatFromContentType(): array
    {
        return [
            ['text/plain', 'plain'],
            ['application/xhtml+xml', 'xml'],
            ['application/hal+json; charset=utf-8', 'json'],
        ];
    }
}
