<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Definition;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Definition\Parameter;

/**
 * Class ParameterTest.
 *
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class ParameterTest extends TestCase
{
    public function testShouldThrowExceptionBecauseItIsBadLocation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('footer is not a supported parameter location, supported: path, header, query, body, formData');

        new Parameter('footer', 'bar');
    }

    /**
     * @dataProvider getData
     */
    public function testShouldBuildParametersWithDefaultValue(string $location)
    {
        $parameter = new Parameter($location, 'foo');
        $this->assertSame($location, $parameter->getLocation());
        $this->assertSame('foo', $parameter->getName());
        $this->assertFalse($parameter->isRequired());
        $this->assertFalse($parameter->hasSchema());
        $this->assertNull($parameter->getSchema());
    }

    /**
     * @dataProvider getData
     */
    public function testShouldBuildParameters(string $location, bool $required, ?\stdClass $schema)
    {
        $parameter = new Parameter($location, 'foo', $required, $schema);
        $this->assertSame($location, $parameter->getLocation());
        $this->assertSame('foo', $parameter->getName());
        $this->assertSame($required, $parameter->isRequired());
        $this->assertSame(null !== $schema, $parameter->hasSchema());
        $this->assertSame($schema, $parameter->getSchema());
    }

    /**
     * @dataProvider getData
     */
    public function testShouldSerializable(string $location, bool $required, ?\stdClass $schema)
    {
        $parameter = new Parameter($location, 'foo', $required, $schema);

        $this->assertEquals($parameter, unserialize(serialize($parameter)));

        $this->assertSame($location, $parameter->getLocation());
        $this->assertSame('foo', $parameter->getName());
        $this->assertSame($required, $parameter->isRequired());
        $this->assertSame(null !== $schema, $parameter->hasSchema());
        $this->assertSame($schema, $parameter->getSchema());
    }

    public function getData(): array
    {
        return [
            ['path', false, null],
            ['header', false, null],
            ['query', false, null],
            ['body', false, null],
            ['formData', false, null],

            ['path', true, null],
            ['header', true, null],
            ['query', true, null],
            ['body', true, null],
            ['formData', true, null],

            ['path', false, new \stdClass()],
            ['header', false, new \stdClass()],
            ['query', false, new \stdClass()],
            ['body', false, new \stdClass()],
            ['formData', false, new \stdClass()],

            ['path', true, new \stdClass()],
            ['header', true, new \stdClass()],
            ['query', true, new \stdClass()],
            ['body', true, new \stdClass()],
            ['formData', true, new \stdClass()],
        ];
    }
}
