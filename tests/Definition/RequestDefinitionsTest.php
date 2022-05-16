<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Definition;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Definition\RequestDefinition;
use TwentytwoLabs\Api\Definition\RequestDefinitions;

/**
 * Class RequestDefinitionsTest.
 *
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class RequestDefinitionsTest extends TestCase
{
    public function testShouldGetRequestDefinition()
    {
        $requestDefinition = $this->createMock(RequestDefinition::class);
        $requestDefinition->expects($this->once())->method('getOperationId')->willReturn('foo');

        $requestDefinitions = new RequestDefinitions([$requestDefinition]);
        $this->assertSame($requestDefinition, $requestDefinitions->getRequestDefinition('foo'));
    }

    public function testShouldNotGetRequestDefinitionBecauseItIsNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find request definition for operationId foo');

        $requestDefinition = $this->createMock(RequestDefinition::class);
        $requestDefinition->expects($this->once())->method('getOperationId')->willReturn('bar');

        $requestDefinitions = new RequestDefinitions([$requestDefinition]);
        $this->assertSame($requestDefinition, $requestDefinitions->getRequestDefinition('foo'));
    }

    public function testShouldValidateEachRequestDefinition()
    {
        $requestDefinitionFoo = $this->createMock(RequestDefinition::class);
        $requestDefinitionFoo->expects($this->once())->method('getOperationId')->willReturn('foo');

        $requestDefinitionBar = $this->createMock(RequestDefinition::class);
        $requestDefinitionBar->expects($this->once())->method('getOperationId')->willReturn('bar');

        $requestDefinitions = new RequestDefinitions([$requestDefinitionFoo, $requestDefinitionBar]);
        $this->assertCount(2, $requestDefinitions);
        foreach ($requestDefinitions as $operationId => $requestDefinition) {
            $this->assertTrue(\in_array($operationId, ['foo', 'bar']));
            $this->assertInstanceOf(RequestDefinition::class, $requestDefinition);
        }
    }

    public function testShouldSerializerRequestDefinition()
    {
        $requestDefinitionFoo = $this->createMock(RequestDefinition::class);
        $requestDefinitionFoo->expects($this->once())->method('getOperationId')->willReturn('foo');

        $requestDefinitionBar = $this->createMock(RequestDefinition::class);
        $requestDefinitionBar->expects($this->once())->method('getOperationId')->willReturn('bar');

        $requestDefinitions = new RequestDefinitions([$requestDefinitionFoo, $requestDefinitionBar]);

        $this->assertEquals($requestDefinitions, unserialize(serialize($requestDefinitions)));
    }
}
