<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Normalizer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Normalizer\QueryParamsNormalizer;

final class QueryParamsNormalizerTest extends TestCase
{
    public function testShouldNormalizeQueryParametersWhenThereAreNoParams(): void
    {
        $jsonSchema = [
            'type' => 'object',
            'properties' => [],
        ];

        $normalizedValue = QueryParamsNormalizer::normalize(['param' => []], $jsonSchema);

        $this->assertSame([], $normalizedValue['param']);
    }

    #[DataProvider('getValidQueryParameters')]
    public function testShouldNormalizeQueryParameters(
        string $schemaType,
        int|string $actualValue,
        mixed $expectedValue,
    ): void {
        $jsonSchema = [
            'type' => 'object',
            'properties' => [
                'param' => [
                    'type' => $schemaType,
                ],
                'foo' => [
                    'type' => $schemaType,
                ],
            ],
        ];

        $normalizedValue = QueryParamsNormalizer::normalize(['param' => $actualValue, 'foo' => $actualValue], $jsonSchema);

        $this->assertSame($expectedValue, $normalizedValue['param']);
        $this->assertSame($expectedValue, $normalizedValue['foo']);
    }

    /**
     * @param array<int, string> $expectedValue
     */
    #[DataProvider('getValidCollectionFormat')]
    public function testShouldTransformCollectionFormatIntoArray(
        string $collectionFormat,
        string $rawValue,
        array $expectedValue,
    ): void {
        $jsonSchema = [
            'type' => 'object',
            'properties' => [
                'param' => [
                    'type' => 'array',
                    'items' => ['string'],
                    'collectionFormat' => $collectionFormat,
                ],
            ],
        ];

        $normalizedValue = QueryParamsNormalizer::normalize(['param' => $rawValue], $jsonSchema);

        $this->assertSame($expectedValue, $normalizedValue['param']);
    }

    public function testShouldThrowAnExceptionOnUnsupportedCollectionFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown is not a supported query collection format');

        $jsonSchema = [
            'type' => 'object',
            'properties' => [
                'param' => [
                    'type' => 'array',
                    'items' => ['string'],
                    'collectionFormat' => 'unknown',
                ],
            ],
        ];

        QueryParamsNormalizer::normalize(['param' => 'foo%bar'], $jsonSchema);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function getValidQueryParameters(): array
    {
        return [
            // description => [schemaType, actual, expected]
            'with an integer' => ['integer', '123', 123],
            'with an integer but is a string' => ['integer', 'foo', 'foo'],
            'with a number' => ['number', '12.15', 12.15],
            'with a number but is a string' => ['number', 'foo', 'foo'],
            'with true given as a string' => ['boolean', 'true', true],
            'with true given as a numeric string' => ['boolean', '1', true],
            'with true given as a numeric' => ['boolean', 1, true],
            'with false given as a string' => ['boolean', 'false', false],
            'with false given as a numeric string' => ['boolean', '0', false],
            'with false given as a numeric' => ['boolean', 0, false],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function getValidCollectionFormat(): array
    {
        return [
            'with csv' => ['csv', 'foo,bar,baz', ['foo', 'bar', 'baz']],
            'with ssv' => ['ssv', 'foo bar baz', ['foo', 'bar', 'baz']],
            'with pipes' => ['pipes', 'foo|bar|baz', ['foo', 'bar', 'baz']],
            'with tabs' => ['tsv', "foo\tbar\tbaz", ['foo', 'bar', 'baz']],
        ];
    }
}
