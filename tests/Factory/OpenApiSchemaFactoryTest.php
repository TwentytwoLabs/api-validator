<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Factory;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinitions;
use TwentytwoLabs\ApiValidator\Definition\Parameter;
use TwentytwoLabs\ApiValidator\Definition\Parameters;
use TwentytwoLabs\ApiValidator\Factory\OpenApiSchemaFactory;
use TwentytwoLabs\ApiValidator\Schema;

final class OpenApiSchemaFactoryTest extends TestCase
{
    public function testShouldNotLoadSchemaBecauseExtension()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#file "[^"]*" does not provide a supported extension choose either json, yml or yaml#');

        $baseFile = './tests/Fixtures/v3/%s';
        $this->getSchema(sprintf('file://%s', realpath(sprintf($baseFile, 'test.txt'))));
    }

    #[DataProvider('getValidFiles')]
    public function testShouldLoadSchemaWithOutBody(string $file, string|int $statusCode)
    {
        $schema = $this->getSchema(sprintf('file://%s', $file));

        $this->assertInstanceOf(OperationDefinitions::class, $schema->getOperationDefinitions());

        $operation = $schema->getOperationDefinition(operationId: 'getImageCollection');

        $this->assertSame($operation, $schema->getOperationDefinition(method: 'GET', path: '/images'));

        $this->assertInstanceOf(OperationDefinition::class, $operation);
        $this->assertSame('GET', $operation->getMethod());
        $this->assertSame('getImageCollection', $operation->getOperationId());
        $this->assertSame('/images', $operation->getPathTemplate());

        $parameters = $operation->getRequestParameters();
        $this->assertInstanceOf(Parameters::class, $parameters);

        $this->assertNull($parameters->getByName('content-type'));
        $this->assertNull($parameters->getByName('body'));

        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['accept'],
                'properties' => [
                    'authorization' => [
                        'type' => 'string',
                        'description' => 'Value for the Authorization header parameter.',
                    ],
                    'x-uid' => [
                        'type' => 'string',
                        'description' => '',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json', 'application/hal+json', 'application/problem+json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );
        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertTrue($operation->hasQueryParametersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => [],
                'properties' => [
                    'apikey' => [
                        'type' => 'string',
                        'description' => 'Value for the Authorization header parameter.',
                    ],
                    'page' => [
                        'type' => 'integer',
                        'default' => 1,
                        'description' => 'The collection page number',
                        'deprecated' => false,
                        'allowEmptyValue' => true,
                        'style' => 'form',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                    'itemsPerPage' => [
                        'type' => 'integer',
                        'default' => 30,
                        'minimum' => 0,
                        'description' => 'The number of items per page',
                        'deprecated' => false,
                        'allowEmptyValue' => true,
                        'style' => 'form',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                ],
            ],
            $operation->getQueryParametersSchema()
        );
        $this->assertFalse($operation->hasBodySchema());
        $this->assertSame([], $operation->getBodySchema());

        $this->assertSame([], $operation->getContentTypes());

        $responseDefinition = $operation->getResponseDefinition($statusCode);
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertTrue($responseDefinition->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['x-version'],
                'properties' => [
                    'x-version' => [
                        'type' => 'string',
                        'description' => '',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                ],
            ],
            $responseDefinition->getHeadersSchema()
        );

        $this->assertSame(['application/json', 'application/hal+json'], $responseDefinition->getContentTypes());

        $this->assertTrue($responseDefinition->hasBodySchema());
        $this->assertSame(
            [
                'application/json' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'description' => '',
                            'deprecated' => false,
                            'properties' => [
                                'title' => [
                                    'type' => 'string',
                                ],
                                'uuid' => [
                                    'type' => 'string',
                                ],
                                'type' => [
                                    'type' => 'string',
                                ],
                                'src' => [
                                    'type' => 'string',
                                ],
                                'status' => [
                                    'type' => 'string',
                                ],
                                'dateCreated' => [
                                    'readOnly' => true,
                                    'type' => 'string',
                                    'format' => 'date-time',
                                ],
                                'dateModified' => [
                                    'readOnly' => true,
                                    'type' => 'string',
                                    'format' => 'date-time',
                                ],
                            ],
                        ],
                        'x-type' => 'array',
                    ],
                ],
                'application/hal+json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            '_embedded' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'description' => '',
                                    'deprecated' => false,
                                    'properties' => [
                                        '_links' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'self' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'href' => [
                                                            'type' => 'string',
                                                            'format' => 'iri-reference',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'title' => [
                                            'type' => 'string',
                                        ],
                                        'uuid' => [
                                            'type' => 'string',
                                        ],
                                        'type' => [
                                            'type' => 'string',
                                        ],
                                        'src' => [
                                            'type' => 'string',
                                        ],
                                        'status' => [
                                            'type' => 'string',
                                        ],
                                        'dateCreated' => [
                                            'readOnly' => true,
                                            'type' => 'string',
                                            'format' => 'date-time',
                                        ],
                                        'dateModified' => [
                                            'readOnly' => true,
                                            'type' => 'string',
                                            'format' => 'date-time',
                                        ],
                                    ],
                                ],
                            ],
                            'totalItems' => [
                                'type' => 'integer',
                                'minimum' => 0,
                            ],
                            'itemsPerPage' => [
                                'type' => 'integer',
                                'minimum' => 0,
                            ],
                            '_links' => [
                                'type' => 'object',
                                'properties' => [
                                    'self' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => [
                                                'type' => 'string',
                                                'format' => 'iri-reference',
                                            ],
                                        ],
                                    ],
                                    'first' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => [
                                                'type' => 'string',
                                                'format' => 'iri-reference',
                                            ],
                                        ],
                                    ],
                                    'last' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => [
                                                'type' => 'string',
                                                'format' => 'iri-reference',
                                            ],
                                        ],
                                    ],
                                    'next' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => [
                                                'type' => 'string',
                                                'format' => 'iri-reference',
                                            ],
                                        ],
                                    ],
                                    'previous' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => [
                                                'type' => 'string',
                                                'format' => 'iri-reference',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'required' => [
                            '_links',
                            '_embedded',
                        ],
                        'x-type' => 'array',
                    ],
                ],
            ],
            $responseDefinition->getBodySchema()
        );

        $defaultDefinition = $operation->getResponseDefinition('500');
        $this->assertSame('default', $defaultDefinition->getStatusCode());
        $this->assertFalse($defaultDefinition->hasHeadersSchema());
        $this->assertSame([], $defaultDefinition->getHeadersSchema());

        $this->assertSame(['application/json', 'application/problem+json'], $defaultDefinition->getContentTypes());

        $this->assertTrue($defaultDefinition->hasBodySchema());
        $this->assertSame(
            [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'detail' => ['type' => 'string'],
                        ],
                    ],
                ],
                'application/problem+json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'detail' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            $defaultDefinition->getBodySchema()
        );
    }

    #[DataProvider('getValidFiles')]
    public function testShouldLoadSchemaWithBody(string $file, string|int $statusCode)
    {
        $schema = $this->getSchema(sprintf('file://%s', $file));

        $this->assertInstanceOf(OperationDefinitions::class, $schema->getOperationDefinitions());

        $operation = $schema->getOperationDefinition(operationId: 'putImageItem');

        $this->assertSame($operation, $schema->getOperationDefinition(method: 'PUT', path: '/images/{uuid}'));

        $this->assertInstanceOf(OperationDefinition::class, $operation);
        $this->assertSame('PUT', $operation->getMethod());
        $this->assertSame('putImageItem', $operation->getOperationId());
        $this->assertSame('/images/{uuid}', $operation->getPathTemplate());

        $parameters = $operation->getRequestParameters();
        $this->assertInstanceOf(Parameters::class, $parameters);

        $contentTypeParameter = $parameters->getByName('content-type');
        $this->assertInstanceOf(Parameter::class, $contentTypeParameter);
        $this->assertSame('header', $contentTypeParameter->getLocation());
        $this->assertSame('content-type', $contentTypeParameter->getName());
        $this->assertTrue($contentTypeParameter->isRequired());
        $this->assertTrue($contentTypeParameter->hasSchema());
        $this->assertSame(
            [
                'type' => 'string',
                'default' => 'application/json',
                'enum' => ['application/json', 'application/hal+json'],
            ],
            $contentTypeParameter->getSchema()
        );

        $bodyParameter = $parameters->getByName('body');
        $this->assertInstanceOf(Parameter::class, $bodyParameter);
        $this->assertSame('body', $bodyParameter->getLocation());
        $this->assertSame('body', $bodyParameter->getName());
        $this->assertTrue($bodyParameter->isRequired());
        $this->assertTrue($bodyParameter->hasSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'description' => '',
                'deprecated' => false,
                'required' => ['title', 'type', 'file'],
                'properties' => [
                    '_links' => [
                        'type' => 'object',
                        'properties' => [
                            'self' => [
                                'type' => 'object',
                                'properties' => [
                                    'href' => [
                                        'type' => 'string',
                                        'format' => 'iri-reference',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'title' => ['type' => 'string'],
                    'type' => ['enum' => ['avatar', 'skill'], 'type' => 'string'],
                    'alternativeText' => ['type' => 'string'],
                    'file' => ['type' => 'string'],
                ],
            ],
            $bodyParameter->getSchema()
        );

        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type', 'accept'],
                'properties' => [
                    'authorization' => [
                        'type' => 'string',
                        'description' => 'Value for the Authorization header parameter.',
                    ],
                    'x-uid' => [
                        'type' => 'string',
                        'description' => '',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json', 'application/hal+json'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json', 'application/hal+json', 'application/problem+json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );
        $this->assertTrue($operation->hasPathSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['uuid'],
                'properties' => [
                    'uuid' => [
                        'type' => 'string',
                        'description' => 'Image identifier',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                ],
            ],
            $operation->getPathSchema()
        );
        $this->assertTrue($operation->hasQueryParametersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => [],
                'properties' => [
                    'apikey' => [
                        'type' => 'string',
                        'description' => 'Value for the Authorization header parameter.',
                    ],
                ],
            ],
            $operation->getQueryParametersSchema()
        );
        $this->assertTrue($operation->hasBodySchema());
        $this->assertSame(
            [
                'type' => 'object',
                'description' => '',
                'deprecated' => false,
                'required' => ['title', 'type', 'file'],
                'properties' => [
                    '_links' => [
                        'type' => 'object',
                        'properties' => [
                            'self' => [
                                'type' => 'object',
                                'properties' => [
                                    'href' => [
                                        'type' => 'string',
                                        'format' => 'iri-reference',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'title' => ['type' => 'string'],
                    'type' => ['enum' => ['avatar', 'skill'], 'type' => 'string'],
                    'alternativeText' => ['type' => 'string'],
                    'file' => ['type' => 'string'],
                ],
            ],
            $operation->getBodySchema()
        );

        $this->assertSame(['application/json', 'application/hal+json'], $operation->getContentTypes());

        $responseDefinition = $operation->getResponseDefinition($statusCode);
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertTrue($responseDefinition->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['x-version'],
                'properties' => [
                    'x-version' => [
                        'type' => 'string',
                        'description' => '',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                ],
            ],
            $responseDefinition->getHeadersSchema()
        );

        $this->assertSame(['application/json', 'application/hal+json'], $responseDefinition->getContentTypes());

        $this->assertTrue($responseDefinition->hasBodySchema());
        $this->assertSame(
            [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'src' => ['type' => 'string'],
                            'alternativeText' => ['nullable' => true, 'type' => 'string'],
                            'status' => ['type' => 'string'],
                            'dateCreated' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                            'dateModified' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
                'application/hal+json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            '_links' => [
                                'type' => 'object',
                                'properties' => [
                                    'self' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => ['type' => 'string', 'format' => 'iri-reference'],
                                        ],
                                    ],
                                ],
                            ],
                            'title' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'src' => ['type' => 'string'],
                            'alternativeText' => ['nullable' => true, 'type' => 'string'],
                            'status' => ['type' => 'string'],
                            'dateCreated' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                            'dateModified' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
            ],
            $responseDefinition->getBodySchema()
        );

        $defaultDefinition = $operation->getResponseDefinition('500');
        $this->assertSame('default', $defaultDefinition->getStatusCode());
        $this->assertTrue($defaultDefinition->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['x-version'],
                'properties' => [
                    'x-version' => [
                        'type' => 'string',
                        'description' => '',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                ],
            ],
            $defaultDefinition->getHeadersSchema()
        );

        $this->assertSame(['application/json', 'application/problem+json'], $defaultDefinition->getContentTypes());

        $this->assertTrue($defaultDefinition->hasBodySchema());
        $this->assertSame(
            [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'detail' => ['type' => 'string'],
                        ],
                    ],
                ],
                'application/problem+json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'detail' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            $defaultDefinition->getBodySchema()
        );
    }

    public static function getValidFiles(): array
    {
        $baseFile = './tests/Fixtures/v3/%s';

        return [
            [realpath(sprintf($baseFile, 'test.yaml')), 200],
            [realpath(sprintf($baseFile, 'test.yml')), 200],
            [realpath(sprintf($baseFile, 'test.json')), 200],
            [realpath(sprintf($baseFile, 'test.yaml')), '200'],
            [realpath(sprintf($baseFile, 'test.yml')), '200'],
            [realpath(sprintf($baseFile, 'test.json')), '200'],
        ];
    }

    #[DataProvider('getFilesWithOutOperationId')]
    public function testShouldLoadSchemaWithoutOperationId(string $file, string|int $statusCode)
    {
        $schema = $this->getSchema(sprintf('file://%s', $file));

        $this->assertInstanceOf(OperationDefinitions::class, $schema->getOperationDefinitions());

        $operation = $schema->getOperationDefinition(method: 'PUT', path: '/images/{uuid}');

        $this->assertInstanceOf(OperationDefinition::class, $operation);
        $this->assertSame('PUT', $operation->getMethod());
        $this->assertSame(32, strlen($operation->getOperationId()));
        $this->assertSame('/images/{uuid}', $operation->getPathTemplate());

        $parameters = $operation->getRequestParameters();
        $this->assertInstanceOf(Parameters::class, $parameters);
        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type', 'accept'],
                'properties' => [
                    'authorization' => [
                        'type' => 'string',
                        'description' => 'Value for the Authorization header parameter.',
                    ],
                    'x-uid' => [
                        'type' => 'string',
                        'description' => '',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json', 'application/hal+json'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json', 'application/hal+json', 'application/problem+json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );
        $this->assertTrue($operation->hasPathSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['uuid'],
                'properties' => [
                    'uuid' => [
                        'type' => 'string',
                        'description' => 'Image identifier',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                ],
            ],
            $operation->getPathSchema()
        );
        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());
        $this->assertTrue($operation->hasBodySchema());
        $this->assertSame(
            [
                'type' => 'object',
                'description' => '',
                'deprecated' => false,
                'required' => ['title', 'type', 'file'],
                'properties' => [
                    '_links' => [
                        'type' => 'object',
                        'properties' => [
                            'self' => [
                                'type' => 'object',
                                'properties' => [
                                    'href' => [
                                        'type' => 'string',
                                        'format' => 'iri-reference',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'title' => ['type' => 'string'],
                    'type' => ['enum' => ['avatar', 'skill'], 'type' => 'string'],
                    'alternativeText' => ['type' => 'string'],
                    'file' => ['type' => 'string'],
                ],
            ],
            $operation->getBodySchema()
        );

        $this->assertSame(['application/json', 'application/hal+json'], $operation->getContentTypes());

        $responseDefinition = $operation->getResponseDefinition($statusCode);

        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertTrue($responseDefinition->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['x-version'],
                'properties' => [
                    'x-version' => [
                        'type' => 'string',
                        'description' => '',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                ],
            ],
            $responseDefinition->getHeadersSchema()
        );

        $this->assertSame(['application/json', 'application/hal+json'], $responseDefinition->getContentTypes());

        $this->assertTrue($responseDefinition->hasBodySchema());
        $this->assertSame(
            [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'src' => ['type' => 'string'],
                            'alternativeText' => ['nullable' => true, 'type' => 'string'],
                            'status' => ['type' => 'string'],
                            'dateCreated' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                            'dateModified' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
                'application/hal+json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            '_links' => [
                                'type' => 'object',
                                'properties' => [
                                    'self' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => ['type' => 'string', 'format' => 'iri-reference'],
                                        ],
                                    ],
                                ],
                            ],
                            'title' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'src' => ['type' => 'string'],
                            'alternativeText' => ['nullable' => true, 'type' => 'string'],
                            'status' => ['type' => 'string'],
                            'dateCreated' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                            'dateModified' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
            ],
            $responseDefinition->getBodySchema()
        );

        $parameters = $responseDefinition->getParameters();
        $this->assertInstanceOf(Parameters::class, $parameters);

        $this->assertTrue($parameters->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['x-version'],
                'properties' => [
                    'x-version' => [
                        'type' => 'string',
                        'description' => '',
                        'deprecated' => false,
                        'allowEmptyValue' => false,
                        'style' => 'simple',
                        'explode' => false,
                        'allowReserved' => false,
                    ],
                ],
            ],
            $parameters->getHeadersSchema()
        );

        $this->assertFalse($parameters->hasPathSchema());
        $this->assertSame([], $parameters->getPathSchema());

        $this->assertFalse($parameters->hasQueryParametersSchema());
        $this->assertSame([], $parameters->getQueryParametersSchema());

        $this->assertTrue($parameters->hasBodySchema());
        $this->assertSame(
            [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'src' => ['type' => 'string'],
                            'alternativeText' => ['nullable' => true, 'type' => 'string'],
                            'status' => ['type' => 'string'],
                            'dateCreated' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                            'dateModified' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
                'application/hal+json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            '_links' => [
                                'type' => 'object',
                                'properties' => [
                                    'self' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => ['type' => 'string', 'format' => 'iri-reference'],
                                        ],
                                    ],
                                ],
                            ],
                            'title' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'src' => ['type' => 'string'],
                            'alternativeText' => ['nullable' => true, 'type' => 'string'],
                            'status' => ['type' => 'string'],
                            'dateCreated' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                            'dateModified' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
            ],
            $parameters->getBodySchema()
        );

        $versionParameter = $parameters->getByName('x-version');
        $this->assertInstanceOf(Parameter::class, $versionParameter);
        $this->assertSame('header', $versionParameter->getLocation());
        $this->assertSame('x-version', $versionParameter->getName());
        $this->assertTrue($versionParameter->isRequired());
        $this->assertTrue($versionParameter->hasSchema());
        $this->assertSame(
            [
                'type' => 'string',
                'description' => '',
                'deprecated' => false,
                'allowEmptyValue' => false,
                'style' => 'simple',
                'explode' => false,
                'allowReserved' => false,
            ],
            $versionParameter->getSchema()
        );

        $bodyParameter = $parameters->getByName('body');
        $this->assertInstanceOf(Parameter::class, $bodyParameter);
        $this->assertSame('body', $bodyParameter->getLocation());
        $this->assertSame('body', $bodyParameter->getName());
        $this->assertTrue($bodyParameter->isRequired());
        $this->assertTrue($bodyParameter->hasSchema());
        $this->assertSame(
            [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'src' => ['type' => 'string'],
                            'alternativeText' => ['nullable' => true, 'type' => 'string'],
                            'status' => ['type' => 'string'],
                            'dateCreated' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                            'dateModified' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
                'application/hal+json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            '_links' => [
                                'type' => 'object',
                                'properties' => [
                                    'self' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'href' => ['type' => 'string', 'format' => 'iri-reference'],
                                        ],
                                    ],
                                ],
                            ],
                            'title' => ['type' => 'string'],
                            'uuid' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'src' => ['type' => 'string'],
                            'alternativeText' => ['nullable' => true, 'type' => 'string'],
                            'status' => ['type' => 'string'],
                            'dateCreated' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                            'dateModified' => ['readOnly' => true, 'type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
            ],
            $bodyParameter->getSchema()
        );
    }

    public static function getFilesWithOutOperationId(): array
    {
        $baseFile = './tests/Fixtures/v3/%s';

        return [
            [realpath(sprintf($baseFile, 'operation-without-an-id.yaml')), 200],
            [realpath(sprintf($baseFile, 'operation-without-an-id.yml')), 200],
            [realpath(sprintf($baseFile, 'operation-without-an-id.json')), 200],
            [realpath(sprintf($baseFile, 'operation-without-an-id.yaml')), '200'],
            [realpath(sprintf($baseFile, 'operation-without-an-id.yml')), '200'],
            [realpath(sprintf($baseFile, 'operation-without-an-id.json')), '200'],
        ];
    }

    #[DataProvider('getFilesWithOutResponses')]
    public function testShouldLoadSchemaWithoutResponse(string $file)
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to specify at least one response for GET /images');

        $this->getSchema(sprintf('file://%s', $file));
    }

    public static function getFilesWithOutResponses(): array
    {
        $baseFile = './tests/Fixtures/v3/%s';

        return [
            [realpath(sprintf($baseFile, 'operation-without-responses.yaml'))],
            [realpath(sprintf($baseFile, 'operation-without-responses.yml'))],
            [realpath(sprintf($baseFile, 'operation-without-responses.json'))],
        ];
    }

    #[DataProvider('getFilesWithOutSecurity')]
    public function testShouldLoadSchemaWithoutSecurity(string $file)
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You must define a security scheme with name apiKey');

        $this->getSchema(sprintf('file://%s', $file));
    }

    public static function getFilesWithOutSecurity(): array
    {
        $baseFile = './tests/Fixtures/v3/%s';

        return [
            [realpath(sprintf($baseFile, 'operation-without-security.yaml'))],
            [realpath(sprintf($baseFile, 'operation-without-security.yml'))],
            [realpath(sprintf($baseFile, 'operation-without-security.json'))],
        ];
    }

    private function getSchema(string $file): Schema
    {
        $factory = new OpenApiSchemaFactory();

        return $factory->createSchema($file);
    }
}
