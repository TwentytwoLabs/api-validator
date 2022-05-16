<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Definition;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Definition\Parameter;
use TwentytwoLabs\Api\Definition\Parameters;

/**
 * Class ParametersTest.
 *
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class ParametersTest extends TestCase
{
    public function testShouldBuildParametersWithoutParameters()
    {
        $parameters = new Parameters([]);
        $this->assertFalse($parameters->hasBodySchema());
        $this->assertNull($parameters->getBodySchema());

        $this->assertFalse($parameters->hasPathSchema());
        $this->assertNull($parameters->getPathSchema());

        $this->assertFalse($parameters->hasQueryParametersSchema());
        $this->assertNull($parameters->getQueryParametersSchema());

        $this->assertFalse($parameters->hasHeadersSchema());
        $this->assertNull($parameters->getHeadersSchema());
        $this->assertNull($parameters->getByName('foo'));
    }

    public function testShouldBuildParameters()
    {
        $schemaFoo = new \stdClass();
        $schemaBar = new \stdClass();

        $parameterFoo = $this->createMock(Parameter::class);
        $parameterFoo->expects($this->atLeastOnce())->method('getName')->willReturn('foo');
        $parameterFoo->expects($this->atLeastOnce())->method('getLocation')->willReturn('path');
        $parameterFoo->expects($this->atLeastOnce())->method('isRequired')->willReturn(true);
        $parameterFoo->expects($this->atLeastOnce())->method('getSchema')->willReturn($schemaFoo);

        $parameterBar = $this->createMock(Parameter::class);
        $parameterBar->expects($this->atLeastOnce())->method('getName')->willReturn('bar');
        $parameterBar->expects($this->atLeastOnce())->method('getLocation')->willReturn('body');
        $parameterBar->expects($this->never())->method('isRequired');
        $parameterBar->expects($this->atLeastOnce())->method('getSchema')->willReturn($schemaBar);
        $parameterBar->expects($this->atLeastOnce())->method('hasSchema')->willReturn(true);

        $parameters = new Parameters([$parameterFoo, $parameterBar]);
        $this->assertCount(2, $parameters);
        foreach ($parameters as $name => $parameter) {
            $this->assertTrue(\in_array($name, ['foo', 'bar']));
            $this->assertInstanceOf(Parameter::class, $parameter);
        }
        $this->assertTrue($parameters->hasBodySchema());
        $this->assertSame($schemaBar, $parameters->getBodySchema());

        $this->assertTrue($parameters->hasPathSchema());
        $this->assertSame(
            ['type' => 'object', 'required' => ['foo'], 'properties' => ['foo' => []]],
            json_decode(json_encode($parameters->getPathSchema()), true)
        );

        $this->assertFalse($parameters->hasQueryParametersSchema());
        $this->assertNull($parameters->getQueryParametersSchema());

        $this->assertFalse($parameters->hasHeadersSchema());
        $this->assertNull($parameters->getHeadersSchema());
        $this->assertSame(['foo' => $parameterFoo], $parameters->getPath());
        $this->assertSame([], $parameters->getQuery());

        $this->assertSame($parameterFoo, $parameters->getByName('foo'));
        $this->assertSame([], $parameters->getHeaders());
        $this->assertSame($parameterBar, $parameters->getBody());
    }

    public function testShouldSerializable()
    {
        $parameterFoo = $this->createMock(Parameter::class);
        $parameterFoo->expects($this->atLeastOnce())->method('getName')->willReturn('foo');
        $parameterFoo->expects($this->never())->method('getLocation');
        $parameterFoo->expects($this->never())->method('isRequired');
        $parameterFoo->expects($this->never())->method('getSchema');

        $parameterBar = $this->createMock(Parameter::class);
        $parameterBar->expects($this->atLeastOnce())->method('getName')->willReturn('bar');
        $parameterBar->expects($this->never())->method('getLocation');
        $parameterBar->expects($this->never())->method('isRequired');
        $parameterBar->expects($this->never())->method('getSchema');

        $parameters = new Parameters([$parameterFoo, $parameterBar]);

        $this->assertEquals($parameters, unserialize(serialize($parameters)));
    }
}
