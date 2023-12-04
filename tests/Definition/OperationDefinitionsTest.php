<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Definition;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinitions;

final class OperationDefinitionsTest extends TestCase
{
    public function testShouldNotGetRequestDefinitionBecauseItIsNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find request definition for operationId foo');

        $requestDefinition = $this->createMock(OperationDefinition::class);
        $requestDefinition->expects($this->once())->method('getOperationId')->willReturn('bar');

        $requestDefinitions = new OperationDefinitions([$requestDefinition]);
        $this->assertSame($requestDefinition, $requestDefinitions->getOperationDefinition('foo'));
    }

    public function testShouldGetRequestDefinition()
    {
        $requestDefinition = $this->createMock(OperationDefinition::class);
        $requestDefinition->expects($this->once())->method('getOperationId')->willReturn('foo');

        $requestDefinitions = new OperationDefinitions([$requestDefinition]);
        $this->assertSame($requestDefinition, $requestDefinitions->getOperationDefinition('foo'));
    }

    public function testShouldValidateEachRequestDefinition()
    {
        $requestDefinitionFoo = $this->createMock(OperationDefinition::class);
        $requestDefinitionFoo->expects($this->once())->method('getOperationId')->willReturn('foo');

        $requestDefinitionBar = $this->createMock(OperationDefinition::class);
        $requestDefinitionBar->expects($this->once())->method('getOperationId')->willReturn('bar');

        $requestDefinitions = new OperationDefinitions([$requestDefinitionFoo, $requestDefinitionBar]);
        $this->assertCount(2, iterator_to_array($requestDefinitions->getIterator()));
        foreach ($requestDefinitions as $operationId => $requestDefinition) {
            $this->assertTrue(\in_array($operationId, ['foo', 'bar']));
            $this->assertInstanceOf(OperationDefinition::class, $requestDefinition);
        }
    }
}
