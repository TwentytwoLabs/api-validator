<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinitions;
use TwentytwoLabs\ApiValidator\Schema;

final class SchemaTest extends TestCase
{
    public function testShouldGetOperationDefinitions()
    {
        $request = $this->createMock(OperationDefinition::class);
        $request->expects($this->once())->method('getOperationId')->willReturn('getPet');

        $requests = new OperationDefinitions([$request]);

        $schema = new Schema($requests);

        $operations = $schema->getOperationDefinitions();

        $this->assertCount(1, iterator_to_array($operations->getIterator()));
    }

    public function testShouldResolveAnOperationIdFromAPathTemplateAndMethod()
    {
        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getMethod')->willReturn('GET');
        $operationDefinition->expects($this->once())->method('getPathTemplate')->willReturn('/api/pets/{id}');
        $operationDefinition->expects($this->never())->method('getOperationId');

        $operationDefinitions = $this->createMock(OperationDefinitions::class);
        $operationDefinitions->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$operationDefinition]));

        $schema = new Schema($operationDefinitions);

        $this->assertSame($operationDefinition, $schema->getOperationDefinition(method: 'GET', path: '/api/pets/1234'));
    }

    public function testShouldResolveAnOperationIdFromAPathAndMethod()
    {
        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getMethod')->willReturn('GET');
        $operationDefinition->expects($this->once())->method('getPathTemplate')->willReturn('/api/pets');
        $operationDefinition->expects($this->never())->method('getOperationId');

        $operationDefinitions = $this->createMock(OperationDefinitions::class);
        $operationDefinitions->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$operationDefinition]));

        $schema = new Schema($operationDefinitions);

        $this->assertSame($operationDefinition, $schema->getOperationDefinition(method: 'GET', path: '/api/pets'));
    }

    public function testShouldThrowAnExceptionWhenNoOperationIdCanBeResolvedBecauseRequestStackIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve the operationId for path /api/pets/1234');

        $requests = new OperationDefinitions();

        $schema = new Schema($requests, '/api');
        $schema->getOperationDefinition(method: 'GET', path: '/api/pets/1234');
    }

    public function testShouldThrowAnExceptionWhenNoOperationIdCanBeResolvedBecauseMethodNotMatching()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve the operationId for path /api');

        $request = $this->createMock(OperationDefinition::class);
        $request->expects($this->once())->method('getMethod')->willReturn('POST');
        $request->expects($this->never())->method('getPathTemplate');

        $requestSecond = $this->createMock(OperationDefinition::class);
        $requestSecond->expects($this->once())->method('getMethod')->willReturn('DELETE');
        $requestSecond->expects($this->never())->method('getPathTemplate');

        $requests = $this->createMock(OperationDefinitions::class);
        $requests->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$request, $requestSecond]));

        $schema = new Schema($requests);

        $schema->getOperationDefinition(method: 'GET', path: '/api');
    }

    public function testShouldThrowAnExceptionWhenNoOperationIdCanBeResolvedBecausePathNotMatching()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve the operationId for path /api');

        $request = $this->createMock(OperationDefinition::class);
        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getPathTemplate')->willReturn('/test');

        $requestSecond = $this->createMock(OperationDefinition::class);
        $requestSecond->expects($this->once())->method('getMethod')->willReturn('DELETE');
        $requestSecond->expects($this->never())->method('getPathTemplate');

        $requests = $this->createMock(OperationDefinitions::class);
        $requests->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$request, $requestSecond]));

        $schema = new Schema($requests);

        $schema->getOperationDefinition(method: 'GET', path: '/api');
    }

    public function testShouldProvideARequestDefinition()
    {
        $request = $this->createMock(OperationDefinition::class);
        $request->expects($this->once())->method('getOperationId')->willReturn('getPet');

        $requests = new OperationDefinitions([$request]);

        $schema = new Schema($requests, '/api');
        $actual = $schema->getOperationDefinition('getPet');

        $this->assertEquals($request, $actual);
    }

    public function testShouldThrowAnExceptionWhenNoRequestDefinitionIsFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find request definition for operationId getPet');

        $requests = new OperationDefinitions();

        $schema = new Schema($requests, '/api');
        $schema->getOperationDefinition('getPet');
    }
}
