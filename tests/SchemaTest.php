<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Definition\RequestDefinition;
use TwentytwoLabs\Api\Definition\RequestDefinitions;
use TwentytwoLabs\Api\Schema;

/**
 * Class SchemaTest.
 */
class SchemaTest extends TestCase
{
    public function testShouldIterateAvailableOperations()
    {
        $request = $this->createMock(RequestDefinition::class);
        $request->expects($this->once())->method('getOperationId')->willReturn('getPet');

        $requests = new RequestDefinitions([$request]);

        $schema = new Schema($requests);

        $operations = $schema->getRequestDefinitions();

        $this->assertTrue(is_iterable($operations));

        foreach ($operations as $operationId => $operation) {
            $this->assertSame('getPet', $operationId);
        }
    }

    public function testShouldResolveAnOperationIdFromAPathTemplateAndMethod()
    {
        $request = $this->createMock(RequestDefinition::class);
        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getPathTemplate')->willReturn('/api/pets/{id}');
        $request->expects($this->once())->method('getOperationId')->willReturn('getPet');

        $requests = $this->createMock(RequestDefinitions::class);
        $requests->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$request]));

        $schema = new Schema($requests);

        $operationId = $schema->findOperationId('GET', '/api/pets/1234');

        $this->assertSame('getPet', $operationId);
    }

    public function testShouldResolveAnOperationIdFromAPathAndMethod()
    {
        $request = $this->createMock(RequestDefinition::class);
        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getPathTemplate')->willReturn('/api/pets');
        $request->expects($this->once())->method('getOperationId')->willReturn('getPets');

        $requests = $this->createMock(RequestDefinitions::class);
        $requests->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$request]));

        $schema = new Schema($requests);

        $operationId = $schema->findOperationId('GET', '/api/pets');

        $this->assertSame('getPets', $operationId);
    }

    public function testShouldThrowAnExceptionWhenNoOperationIdCanBeResolvedBecauseRequestStackIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve the operationId for path /api/pets/1234');

        $requests = new RequestDefinitions();

        $schema = new Schema($requests, '/api');
        $schema->findOperationId('GET', '/api/pets/1234');
    }

    public function testShouldThrowAnExceptionWhenNoOperationIdCanBeResolvedBecauseMethodNotMatching()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve the operationId for path /api');

        $request = $this->createMock(RequestDefinition::class);
        $request->expects($this->once())->method('getMethod')->willReturn('POST');
        $request->expects($this->never())->method('getPathTemplate');

        $requestSecond = $this->createMock(RequestDefinition::class);
        $requestSecond->expects($this->once())->method('getMethod')->willReturn('DELETE');
        $requestSecond->expects($this->never())->method('getPathTemplate');

        $requests = $this->createMock(RequestDefinitions::class);
        $requests->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$request, $requestSecond]));

        $schema = new Schema($requests);

        $schema->findOperationId('GET', '/api');
    }

    public function testShouldThrowAnExceptionWhenNoOperationIdCanBeResolvedBecausePathNotMatching()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve the operationId for path /api');

        $request = $this->createMock(RequestDefinition::class);
        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getPathTemplate')->willReturn('/test');

        $requestSecond = $this->createMock(RequestDefinition::class);
        $requestSecond->expects($this->once())->method('getMethod')->willReturn('DELETE');
        $requestSecond->expects($this->never())->method('getPathTemplate');

        $requests = $this->createMock(RequestDefinitions::class);
        $requests->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([$request, $requestSecond]));

        $schema = new Schema($requests);

        $schema->findOperationId('GET', '/api');
    }

    public function testShouldProvideARequestDefinition()
    {
        $request = $this->createMock(RequestDefinition::class);
        $request->expects($this->once())->method('getOperationId')->willReturn('getPet');

        $requests = new RequestDefinitions([$request]);

        $schema = new Schema($requests, '/api');
        $actual = $schema->getRequestDefinition('getPet');

        $this->assertEquals($request, $actual);
    }

    public function testShouldThrowAnExceptionWhenNoRequestDefinitionIsFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find request definition for operationId getPet');

        $requests = new RequestDefinitions();

        $schema = new Schema($requests, '/api');
        $schema->getRequestDefinition('getPet');
    }

    public function testShouldSerialized()
    {
        $requests = new RequestDefinitions();

        $schema = new Schema($requests);

        $this->assertEquals($schema, unserialize(serialize($schema)));
    }
}
