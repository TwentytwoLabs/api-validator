<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Definition;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\Parameter;
use TwentytwoLabs\ApiValidator\Definition\Parameters;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;

final class OperationDefinitionTest extends TestCase
{
    public function testShouldNotGetResponseDefinitionBecauseItIsNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No response definition for GET /features is available for status code 500');

        $headersSchema = [
            'type' => 'object',
            'required' => ['content-type'],
            'properties' => [
                'x-uid' => ['type' => 'string', 'description' => ''],
                'content-type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
            ],
        ];
        $queryParametersSchema = [
            'type' => 'object',
            'required' => [],
            'properties' => [
                'page' => ['type' => 'integer', 'default' => 1],
                'itemsPerPage' => ['type' => 'integer', 'default' => 30, 'minimum' => 0],
                'enabled' => ['type' => 'boolean'],
                'order[createdBy]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[updatedBy]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[dateCreated]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[dateModified]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'name' => ['type' => 'string'],
            ],
        ];

        $contentType = new Parameter(
            location: 'header',
            name: 'content-type',
            required: true,
            schema: ['enum' => ['application/json']]
        );

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('getStatusCode')->willReturn(200);

        $requestDefinition = new OperationDefinition(
            'GET',
            'getFeatureCollection',
            '/features',
            new Parameters([$contentType]),
            [$responseDefinition]
        );

        $this->assertSame('GET', $requestDefinition->getMethod());
        $this->assertSame('getFeatureCollection', $requestDefinition->getOperationId());
        $this->assertSame('/features', $requestDefinition->getPathTemplate());

        $requestParameters = $requestDefinition->getRequestParameters();
        $this->assertInstanceOf(Parameters::class, $requestParameters);
        $this->assertTrue($requestParameters->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type'],
                'properties' => [
                    'content-type' => [
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $requestParameters->getHeadersSchema()
        );
        $this->assertSame(['content-type' => $contentType], $requestParameters->getHeaders());
        $this->assertFalse($requestParameters->hasPathSchema());
        $this->assertSame([], $requestParameters->getPathSchema());
        $this->assertSame([], $requestParameters->getPath());
        $this->assertFalse($requestParameters->hasQueryParametersSchema());
        $this->assertSame([], $requestParameters->getQueryParametersSchema());
        $this->assertSame([], $requestParameters->getQuery());
        $this->assertFalse($requestParameters->hasBodySchema());
        $this->assertSame([], $requestParameters->getBodySchema());
        $this->assertNull($requestParameters->getBody());

        $this->assertSame(['application/json'], $requestDefinition->getContentTypes());

        $this->assertSame($responseDefinition, $requestDefinition->getResponseDefinition(500));

        $this->assertTrue($requestDefinition->hasHeadersSchema());
        $this->assertSame($headersSchema, $requestDefinition->getHeadersSchema());

        $this->assertFalse($requestDefinition->hasPathSchema());
        $this->assertEmpty($requestDefinition->getPathSchema());

        $this->assertTrue($requestDefinition->hasQueryParametersSchema());
        $this->assertSame($queryParametersSchema, $requestDefinition->getQueryParametersSchema());

        $this->assertFalse($requestDefinition->hasBodySchema());
        $this->assertEmpty($requestDefinition->getBodySchema());
    }

    public function testShouldGetResponseDefinition()
    {
        $headersSchema = [
            'type' => 'object',
            'required' => ['content-type'],
            'properties' => [
                'content-type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
            ],
        ];
        $queryParametersSchema = [
            'type' => 'object',
            'required' => [],
            'properties' => [
                'page' => ['type' => 'integer', 'default' => 1],
                'itemsPerPage' => ['type' => 'integer', 'default' => 30, 'minimum' => 0],
                'enabled' => ['type' => 'boolean'],
                'order[createdBy]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[updatedBy]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[dateCreated]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[dateModified]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'name' => ['type' => 'string'],
            ],
        ];

        $contentType = new Parameter(
            location: 'header',
            name: 'content-type',
            required: true,
            schema: ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']]
        );

        $page = new Parameter(location: 'query', name: 'page', schema: ['type' => 'integer', 'default' => 1]);
        $itemsPerPage = new Parameter(location: 'query', name: 'itemsPerPage', schema: ['type' => 'integer', 'default' => 30, 'minimum' => 0]);
        $enabled = new Parameter(location: 'query', name: 'enabled', schema: ['type' => 'boolean']);
        $orderCreatedBy = new Parameter(location: 'query', name: 'order[createdBy]', schema: ['type' => 'string', 'enum' => ['asc', 'desc']]);
        $orderUpdatedBy = new Parameter(location: 'query', name: 'order[updatedBy]', schema: ['type' => 'string', 'enum' => ['asc', 'desc']]);
        $orderDateCreated = new Parameter(location: 'query', name: 'order[dateCreated]', schema: ['type' => 'string', 'enum' => ['asc', 'desc']]);
        $orderDateModified = new Parameter(location: 'query', name: 'order[dateModified]', schema: ['type' => 'string', 'enum' => ['asc', 'desc']]);
        $name = new Parameter(location: 'query', name: 'name', schema: ['type' => 'string']);

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('getStatusCode')->willReturn(200);

        $defaultResponseDefinition = $this->createMock(ResponseDefinition::class);
        $defaultResponseDefinition->expects($this->once())->method('getStatusCode')->willReturn('default');

        $requestDefinition = new OperationDefinition(
            'GET',
            'getFeatureCollection',
            '/features',
            new Parameters([
                $contentType,
                $page,
                $itemsPerPage,
                $enabled,
                $orderCreatedBy,
                $orderUpdatedBy,
                $orderDateCreated,
                $orderDateModified,
                $name,
            ]),
            [$responseDefinition, $defaultResponseDefinition]
        );

        $this->assertSame('GET', $requestDefinition->getMethod());
        $this->assertSame('getFeatureCollection', $requestDefinition->getOperationId());
        $this->assertSame('/features', $requestDefinition->getPathTemplate());

        $requestParameters = $requestDefinition->getRequestParameters();
        $this->assertInstanceOf(Parameters::class, $requestParameters);
        $this->assertTrue($requestParameters->hasHeadersSchema());
        $this->assertSame($headersSchema, $requestParameters->getHeadersSchema());
        $this->assertSame(['content-type' => $contentType], $requestParameters->getHeaders());
        $this->assertFalse($requestParameters->hasPathSchema());
        $this->assertSame([], $requestParameters->getPathSchema());
        $this->assertSame([], $requestParameters->getPath());
        $this->assertTrue($requestParameters->hasQueryParametersSchema());
        $this->assertSame($queryParametersSchema, $requestParameters->getQueryParametersSchema());
        $this->assertSame(
            [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'enabled' => $enabled,
                'order[createdBy]' => $orderCreatedBy,
                'order[updatedBy]' => $orderUpdatedBy,
                'order[dateCreated]' => $orderDateCreated,
                'order[dateModified]' => $orderDateModified,
                'name' => $name,
            ],
            $requestParameters->getQuery()
        );
        $this->assertFalse($requestParameters->hasBodySchema());
        $this->assertSame([], $requestParameters->getBodySchema());
        $this->assertNull($requestParameters->getBody());
        $this->assertSame(['application/json'], $requestDefinition->getContentTypes());

        $this->assertSame($responseDefinition, $requestDefinition->getResponseDefinition(200));

        $this->assertTrue($requestDefinition->hasHeadersSchema());
        $this->assertSame($headersSchema, $requestDefinition->getHeadersSchema());

        $this->assertFalse($requestDefinition->hasPathSchema());
        $this->assertEmpty($requestDefinition->getPathSchema());

        $this->assertTrue($requestDefinition->hasQueryParametersSchema());
        $this->assertSame($queryParametersSchema, $requestDefinition->getQueryParametersSchema());

        $this->assertFalse($requestDefinition->hasBodySchema());
        $this->assertEmpty($requestDefinition->getBodySchema());
    }

    public function testShouldGetResponseDefinitionUsingDefaultResponse()
    {
        $headersSchema = [
            'type' => 'object',
            'required' => ['content-type'],
            'properties' => [
                'content-type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
            ],
        ];
        $queryParametersSchema = [
            'type' => 'object',
            'required' => [],
            'properties' => [
                'page' => ['type' => 'integer', 'default' => 1],
                'itemsPerPage' => ['type' => 'integer', 'default' => 30, 'minimum' => 0],
                'enabled' => ['type' => 'boolean'],
                'order[createdBy]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[updatedBy]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[dateCreated]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'order[dateModified]' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                'name' => ['type' => 'string'],
            ],
        ];

        $contentType = new Parameter(
            location: 'header',
            name: 'content-type',
            required: true,
            schema: ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']]
        );

        $page = new Parameter(location: 'query', name: 'page', schema: ['type' => 'integer', 'default' => 1]);
        $itemsPerPage = new Parameter(location: 'query', name: 'itemsPerPage', schema: ['type' => 'integer', 'default' => 30, 'minimum' => 0]);
        $enabled = new Parameter(location: 'query', name: 'enabled', schema: ['type' => 'boolean']);
        $orderCreatedBy = new Parameter(location: 'query', name: 'order[createdBy]', schema: ['type' => 'string', 'enum' => ['asc', 'desc']]);
        $orderUpdatedBy = new Parameter(location: 'query', name: 'order[updatedBy]', schema: ['type' => 'string', 'enum' => ['asc', 'desc']]);
        $orderDateCreated = new Parameter(location: 'query', name: 'order[dateCreated]', schema: ['type' => 'string', 'enum' => ['asc', 'desc']]);
        $orderDateModified = new Parameter(location: 'query', name: 'order[dateModified]', schema: ['type' => 'string', 'enum' => ['asc', 'desc']]);
        $name = new Parameter(location: 'query', name: 'name', schema: ['type' => 'string']);

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('getStatusCode')->willReturn(200);

        $defaultResponseDefinition = $this->createMock(ResponseDefinition::class);
        $defaultResponseDefinition->expects($this->once())->method('getStatusCode')->willReturn('default');

        $requestDefinition = new OperationDefinition(
            'GET',
            'getFeatureCollection',
            '/features',
            new Parameters([
                $contentType,
                $page,
                $itemsPerPage,
                $enabled,
                $orderCreatedBy,
                $orderUpdatedBy,
                $orderDateCreated,
                $orderDateModified,
                $name,
            ]),
            [$responseDefinition, $defaultResponseDefinition]
        );

        $this->assertSame('GET', $requestDefinition->getMethod());
        $this->assertSame('getFeatureCollection', $requestDefinition->getOperationId());
        $this->assertSame('/features', $requestDefinition->getPathTemplate());

        $requestParameters = $requestDefinition->getRequestParameters();
        $this->assertInstanceOf(Parameters::class, $requestParameters);
        $this->assertTrue($requestParameters->hasHeadersSchema());
        $this->assertSame($headersSchema, $requestParameters->getHeadersSchema());
        $this->assertSame(['content-type' => $contentType], $requestParameters->getHeaders());
        $this->assertFalse($requestParameters->hasPathSchema());
        $this->assertSame([], $requestParameters->getPathSchema());
        $this->assertSame([], $requestParameters->getPath());
        $this->assertTrue($requestParameters->hasQueryParametersSchema());
        $this->assertSame($queryParametersSchema, $requestParameters->getQueryParametersSchema());
        $this->assertSame(
            [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'enabled' => $enabled,
                'order[createdBy]' => $orderCreatedBy,
                'order[updatedBy]' => $orderUpdatedBy,
                'order[dateCreated]' => $orderDateCreated,
                'order[dateModified]' => $orderDateModified,
                'name' => $name,
            ],
            $requestParameters->getQuery()
        );
        $this->assertFalse($requestParameters->hasBodySchema());
        $this->assertSame([], $requestParameters->getBodySchema());
        $this->assertNull($requestParameters->getBody());
        $this->assertSame(['application/json'], $requestDefinition->getContentTypes());

        $this->assertNotSame($responseDefinition, $requestDefinition->getResponseDefinition(500));
        $this->assertSame($defaultResponseDefinition, $requestDefinition->getResponseDefinition(500));

        $this->assertTrue($requestDefinition->hasHeadersSchema());
        $this->assertSame($headersSchema, $requestDefinition->getHeadersSchema());

        $this->assertFalse($requestDefinition->hasPathSchema());
        $this->assertEmpty($requestDefinition->getPathSchema());

        $this->assertTrue($requestDefinition->hasQueryParametersSchema());
        $this->assertSame($queryParametersSchema, $requestDefinition->getQueryParametersSchema());

        $this->assertFalse($requestDefinition->hasBodySchema());
        $this->assertEmpty($requestDefinition->getBodySchema());
    }
}
