<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Definition;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Definition\Parameter;

final class ParameterTest extends TestCase
{
    public function testShouldThrowExceptionBecauseItIsBadLocation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('footer is not a supported parameter location, supported: header, path, query, body, formData');

        new Parameter('footer', 'bar');
    }

    /**
     * @param array<string, mixed> $schema
     */
    #[DataProvider('getData')]
    public function testShouldBuildParametersWithDefaultValue(string $location, bool $required, array $schema): void
    {
        $parameter = new Parameter($location, 'foo');
        $this->assertSame($location, $parameter->getLocation());
        $this->assertSame('foo', $parameter->getName());
        $this->assertFalse($parameter->isRequired());
        $this->assertFalse($parameter->hasSchema());
        $this->assertEmpty($parameter->getSchema());
    }

    /**
     * @param array<int|string, mixed> $schema
     */
    #[DataProvider('getData')]
    public function testShouldBuildParameters(string $location, bool $required, array $schema): void
    {
        $parameter = new Parameter($location, 'foo', $required, $schema);
        $this->assertSame($location, $parameter->getLocation());
        $this->assertSame('foo', $parameter->getName());
        $this->assertSame($required, $parameter->isRequired());
        $this->assertSame(!empty($schema), $parameter->hasSchema());
        $this->assertSame($schema, $parameter->getSchema());
    }

    /**
     * @param array<int|string, mixed> $schema
     */
    #[DataProvider('getData')]
    public function testShouldSerializable(string $location, bool $required, array $schema): void
    {
        $parameter = new Parameter($location, 'foo', $required, $schema);

        $this->assertEquals($parameter, unserialize(serialize($parameter)));

        $this->assertSame($location, $parameter->getLocation());
        $this->assertSame('foo', $parameter->getName());
        $this->assertSame($required, $parameter->isRequired());
        $this->assertSame(!empty($schema), $parameter->hasSchema());
        $this->assertSame($schema, $parameter->getSchema());
    }

    /**
     * @return array<int, mixed>
     */
    public static function getData(): array
    {
        return [
            ['path', false, []],
            ['header', false, []],
            ['query', false, []],
            ['body', false, []],
            ['formData', false, []],

            ['path', true, []],
            ['header', true, []],
            ['query', true, []],
            ['body', true, []],
            ['formData', true, []],
        ];
    }
}
