<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Definition;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Definition\Parameters;
use TwentytwoLabs\Api\Definition\RequestDefinition;
use TwentytwoLabs\Api\Definition\ResponseDefinition;

/**
 * Class RequestDefinitionTest.
 */
class RequestDefinitionTest extends TestCase
{
    public function testShouldSerialized()
    {
        $requestParameters = $this->createMock(Parameters::class);

        $requestDefinition = new RequestDefinition(
            'GET',
            'getFoo',
            '/foo/{id}',
            $requestParameters,
            ['application/json'],
            [],
            []
        );

        $serialized = serialize($requestDefinition);

        $this->assertEquals($requestDefinition, unserialize($serialized));
        $this->assertSame('GET', $requestDefinition->getMethod());
        $this->assertSame('getFoo', $requestDefinition->getOperationId());
        $this->assertSame('/foo/{id}', $requestDefinition->getPathTemplate());

        $this->assertSame($requestParameters, $requestDefinition->getRequestParameters());

        $this->assertSame(['application/json'], $requestDefinition->getContentTypes());
        $this->assertSame([], $requestDefinition->getAccepts());

        $this->assertFalse($requestDefinition->hasBodySchema());
        $this->assertNull($requestDefinition->getBodySchema());

        $this->assertFalse($requestDefinition->hasHeadersSchema());
        $this->assertNull($requestDefinition->getHeadersSchema());

        $this->assertFalse($requestDefinition->hasPathSchema());
        $this->assertNull($requestDefinition->getPathSchema());

        $this->assertFalse($requestDefinition->hasQueryParametersSchema());
        $this->assertNull($requestDefinition->getQueryParametersSchema());
    }

    public function testShouldProvideAResponseDefinition()
    {
        $requestParameters = $this->createMock(Parameters::class);

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('getStatusCode')->willReturn(200);

        $requestDefinition = new RequestDefinition(
            'GET',
            'getFoo',
            '/foo/{id}',
            $requestParameters,
            ['application/json'],
            [],
            [$responseDefinition]
        );
        $this->assertSame('GET', $requestDefinition->getMethod());
        $this->assertSame('getFoo', $requestDefinition->getOperationId());
        $this->assertSame('/foo/{id}', $requestDefinition->getPathTemplate());

        $this->assertSame($requestParameters, $requestDefinition->getRequestParameters());

        $this->assertSame(['application/json'], $requestDefinition->getContentTypes());
        $this->assertSame([], $requestDefinition->getAccepts());

        $this->assertFalse($requestDefinition->hasBodySchema());
        $this->assertNull($requestDefinition->getBodySchema());

        $this->assertFalse($requestDefinition->hasHeadersSchema());
        $this->assertNull($requestDefinition->getHeadersSchema());

        $this->assertFalse($requestDefinition->hasPathSchema());
        $this->assertNull($requestDefinition->getPathSchema());

        $this->assertFalse($requestDefinition->hasQueryParametersSchema());
        $this->assertNull($requestDefinition->getQueryParametersSchema());

        $this->assertInstanceOf(ResponseDefinition::class, $requestDefinition->getResponseDefinition(200));
    }

    public function testShouldProvideAResponseDefinitionUsingDefaultValue()
    {
        $statusCodes = [200, 'default'];
        $responseDefinitions = [];
        foreach ($statusCodes as $statusCode) {
            $responseDefinition = $this->createMock(ResponseDefinition::class);
            $responseDefinition->expects($this->once())->method('getStatusCode')->willReturn($statusCode);
            $responseDefinitions[$statusCode] = $responseDefinition;
        }

        $requestParameters = $this->createMock(Parameters::class);

        $requestDefinition = new RequestDefinition(
            'GET',
            'getFoo',
            '/foo/{id}',
            $requestParameters,
            ['application/json'],
            [],
            $responseDefinitions
        );
        $this->assertSame('GET', $requestDefinition->getMethod());
        $this->assertSame('getFoo', $requestDefinition->getOperationId());
        $this->assertSame('/foo/{id}', $requestDefinition->getPathTemplate());

        $this->assertSame($requestParameters, $requestDefinition->getRequestParameters());

        $this->assertSame(['application/json'], $requestDefinition->getContentTypes());
        $this->assertSame([], $requestDefinition->getAccepts());

        $this->assertFalse($requestDefinition->hasBodySchema());
        $this->assertNull($requestDefinition->getBodySchema());

        $this->assertFalse($requestDefinition->hasHeadersSchema());
        $this->assertNull($requestDefinition->getHeadersSchema());

        $this->assertFalse($requestDefinition->hasPathSchema());
        $this->assertNull($requestDefinition->getPathSchema());

        $this->assertFalse($requestDefinition->hasQueryParametersSchema());
        $this->assertNull($requestDefinition->getQueryParametersSchema());

        $this->assertInstanceOf(ResponseDefinition::class, $requestDefinition->getResponseDefinition(500));
        $this->assertSame($responseDefinitions['default'], $requestDefinition->getResponseDefinition(500));
    }

    public function testShouldThrowAnExceptionWhenNoResponseDefinitionIsFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No response definition for GET /foo/{id} is available for status code 200');

        $requestParameters = $this->createMock(Parameters::class);

        $requestDefinition = new RequestDefinition(
            'GET',
            'getFoo',
            '/foo/{id}',
            $requestParameters,
            ['application/json'],
            [],
            []
        );
        $this->assertSame('GET', $requestDefinition->getMethod());
        $this->assertSame('getFoo', $requestDefinition->getOperationId());
        $this->assertSame('/foo/{id}', $requestDefinition->getPathTemplate());

        $this->assertSame($requestParameters, $requestDefinition->getRequestParameters());

        $this->assertSame(['application/json'], $requestDefinition->getContentTypes());
        $this->assertSame([], $requestDefinition->getAccepts());

        $this->assertFalse($requestDefinition->hasBodySchema());
        $this->assertNull($requestDefinition->getBodySchema());

        $this->assertFalse($requestDefinition->hasHeadersSchema());
        $this->assertNull($requestDefinition->getHeadersSchema());

        $this->assertFalse($requestDefinition->hasPathSchema());
        $this->assertNull($requestDefinition->getPathSchema());

        $this->assertFalse($requestDefinition->hasQueryParametersSchema());
        $this->assertNull($requestDefinition->getQueryParametersSchema());

        $requestDefinition->getResponseDefinition(200);
    }
}
