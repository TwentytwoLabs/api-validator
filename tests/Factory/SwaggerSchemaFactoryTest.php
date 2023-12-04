<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Factory;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinitions;
use TwentytwoLabs\ApiValidator\Definition\Parameter;
use TwentytwoLabs\ApiValidator\Definition\Parameters;
use TwentytwoLabs\ApiValidator\Factory\SwaggerSchemaFactory;
use TwentytwoLabs\ApiValidator\Schema;

final class SwaggerSchemaFactoryTest extends TestCase
{
    public function testShouldNotLoadSchemaBecauseMissingResponse(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to specify at least one response for GET /something');

        $this->getSchema(sprintf('file://%s', realpath('./tests/Fixtures/v2/operation-without-responses.json')));
    }

    public function testShouldLoadSchemaWhenRequestHasConflictingLocations(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Parameters cannot have body and formData locations at the same time in /post/with-conflicting-locations');

        $file = realpath('./tests/Fixtures/v2/request-with-conflicting-locations.json');
        $this->getSchema(sprintf('file://%s', $file));
    }

    public function testShouldLoadSchemaWithoutOperationId()
    {
        $file = realpath('./tests/Fixtures/v2/operation-without-an-id.json');
        $schema = $this->getSchema(sprintf('file://%s', $file));

        $this->assertInstanceOf(OperationDefinitions::class, $schema->getOperationDefinitions());

        $operation = $schema->getOperationDefinition(method: 'GET', path: '/something');

        $this->assertInstanceOf(OperationDefinition::class, $operation);
        $this->assertSame('GET', $operation->getMethod());
        $this->assertNotEmpty($operation->getOperationId());
        $this->assertSame(32, strlen($operation->getOperationId()));
        $this->assertSame('/something', $operation->getPathTemplate());

        $parameters = $operation->getRequestParameters();
        $this->assertInstanceOf(Parameters::class, $parameters);

        $contentTypeParameter = $parameters->getByName('content-type');
        $this->assertNull($contentTypeParameter);

        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['accept'],
                'properties' => [
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => [],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertSame('/something', $operation->getPathTemplate());

        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());

        $this->assertNull($parameters->getByName('user'));
        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => [
                    'accept',
                ],
                'properties' => [
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => [],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());
        $this->assertFalse($operation->hasBodySchema());
        $this->assertSame([], $operation->getBodySchema());

        $this->assertSame([], $operation->getContentTypes());

        $responseDefinition = $operation->getResponseDefinition('200');
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertFalse($responseDefinition->hasHeadersSchema());
        $this->assertSame([], $responseDefinition->getHeadersSchema());
        $this->assertFalse($responseDefinition->hasBodySchema());
        $this->assertSame([], $responseDefinition->getBodySchema());
    }

    public function testShouldLoadSchemaWithoutContentTypeInBody()
    {
        $file = realpath('./tests/Fixtures/v2/request-without-content-types.json');
        $schema = $this->getSchema(sprintf('file://%s', $file));

        $this->assertInstanceOf(OperationDefinitions::class, $schema->getOperationDefinitions());

        $operation = $schema->getOperationDefinition(operationId: 'postBodyWithoutAContentType');
        $this->assertSame($operation, $schema->getOperationDefinition(method: 'POST', path: '/post/body-without-a-content-type'));

        $this->assertInstanceOf(OperationDefinition::class, $operation);
        $this->assertSame('POST', $operation->getMethod());
        $this->assertSame('postBodyWithoutAContentType', $operation->getOperationId());
        $this->assertSame('/post/body-without-a-content-type', $operation->getPathTemplate());

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
                'enum' => ['application/json'],
            ],
            $contentTypeParameter->getSchema()
        );

        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type', 'accept'],
                'properties' => [
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertSame('/post/body-without-a-content-type', $operation->getPathTemplate());

        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());

        $this->assertNull($parameters->getByName('user'));
        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type', 'accept'],
                'properties' => [
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());
        $this->assertTrue($operation->hasBodySchema());
        $this->assertSame(
            ['type' => 'object'],
            $operation->getBodySchema()
        );

        $this->assertSame(['application/json'], $operation->getContentTypes());

        $responseDefinition = $operation->getResponseDefinition('200');
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertFalse($responseDefinition->hasHeadersSchema());
        $this->assertSame([], $responseDefinition->getHeadersSchema());
        $this->assertFalse($responseDefinition->hasBodySchema());
        $this->assertSame([], $responseDefinition->getBodySchema());
    }

    public function testShouldLoadSchemaWithoutContentTypeInFormData()
    {
        $file = realpath('./tests/Fixtures/v2/request-without-content-types.json');
        $schema = $this->getSchema(sprintf('file://%s', $file));

        $this->assertInstanceOf(OperationDefinitions::class, $schema->getOperationDefinitions());

        $operation = $schema->getOperationDefinition(operationId: 'postFromDataWithoutAContentType');
        $this->assertSame($operation, $schema->getOperationDefinition(method: 'POST', path: '/post/form-data-without-a-content-type'));

        $this->assertInstanceOf(OperationDefinition::class, $operation);
        $this->assertSame('POST', $operation->getMethod());
        $this->assertSame('postFromDataWithoutAContentType', $operation->getOperationId());
        $this->assertSame('/post/form-data-without-a-content-type', $operation->getPathTemplate());

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
                'enum' => ['application/x-www-form-urlencoded'],
            ],
            $contentTypeParameter->getSchema()
        );

        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type', 'accept'],
                'properties' => [
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/x-www-form-urlencoded'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertSame('/post/form-data-without-a-content-type', $operation->getPathTemplate());

        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());

        $this->assertNull($parameters->getByName('user'));
        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type', 'accept'],
                'properties' => [
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/x-www-form-urlencoded'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());
        $this->assertFalse($operation->hasBodySchema());
        $this->assertSame([], $operation->getBodySchema());

        $this->assertSame(['application/x-www-form-urlencoded'], $operation->getContentTypes());

        $responseDefinition = $operation->getResponseDefinition('200');
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertFalse($responseDefinition->hasHeadersSchema());
        $this->assertSame([], $responseDefinition->getHeadersSchema());
        $this->assertFalse($responseDefinition->hasBodySchema());
        $this->assertSame([], $responseDefinition->getBodySchema());
    }

    public function testShouldLoadSchemaWithDefaults(): void
    {
        $file = realpath('./tests/Fixtures/v2/schema-with-default-consumes-and-produces-properties.json');
        $schema = $this->getSchema(sprintf('file://%s', $file));

        $this->assertInstanceOf(OperationDefinitions::class, $schema->getOperationDefinitions());

        $operation = $schema->getOperationDefinition(operationId: 'postSomething');

        $this->assertSame($operation, $schema->getOperationDefinition(method: 'POST', path: '/something'));

        $this->assertInstanceOf(OperationDefinition::class, $operation);
        $this->assertSame('POST', $operation->getMethod());
        $this->assertSame('postSomething', $operation->getOperationId());
        $this->assertSame('/something', $operation->getPathTemplate());

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
                'enum' => ['application/json'],
            ],
            $contentTypeParameter->getSchema()
        );

        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type', 'accept'],
                'properties' => [
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertSame('/something', $operation->getPathTemplate());

        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());

        $this->assertNull($parameters->getByName('user'));
        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => [
                    'content-type',
                    'accept',
                ],
                'properties' => [
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertFalse($operation->hasPathSchema());
        $this->assertSame([], $operation->getPathSchema());
        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());
        $this->assertFalse($operation->hasBodySchema());
        $this->assertSame([], $operation->getBodySchema());

        $this->assertSame(['application/json'], $operation->getContentTypes());
    }

    #[DataProvider('getValidFiles')]
    public function testShouldLoadSchemaWithBody(string $file, string|int $statusCode): void
    {
        $schema = $this->getSchema(sprintf('file://%s', $file));

        $this->assertInstanceOf(OperationDefinitions::class, $schema->getOperationDefinitions());

        $operation = $schema->getOperationDefinition(operationId: 'putUserItem');

        $this->assertSame($operation, $schema->getOperationDefinition(method: 'PUT', path: '/users/{slug}'));

        $this->assertInstanceOf(OperationDefinition::class, $operation);
        $this->assertSame('PUT', $operation->getMethod());
        $this->assertSame('putUserItem', $operation->getOperationId());
        $this->assertSame('/users/{slug}', $operation->getPathTemplate());

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
                'enum' => ['application/hal+json', 'text/html', 'application/json'],
            ],
            $contentTypeParameter->getSchema()
        );

        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['authorization', 'x-authorization', 'content-type', 'accept'],
                'properties' => [
                    'authorization' => ['type' => 'string', 'description' => 'Value for the Authorization header'],
                    'x-authorization' => ['type' => 'string', 'description' => 'Value for the Authorization header'],
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/hal+json', 'text/html', 'application/json'],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json', 'application/hal+json', 'text/html', 'application/problem+json'],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertTrue($operation->hasPathSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['slug'],
                'properties' => ['slug' => ['type' => 'string']],
            ],
            $operation->getPathSchema()
        );
        $this->assertSame('/users/{slug}', $operation->getPathTemplate());

        $this->assertFalse($operation->hasQueryParametersSchema());
        $this->assertSame([], $operation->getQueryParametersSchema());

        $bodyParameter = $parameters->getByName('user');
        $this->assertInstanceOf(Parameter::class, $bodyParameter);
        $this->assertSame('body', $bodyParameter->getLocation());
        $this->assertSame('user', $bodyParameter->getName());
        $this->assertTrue($bodyParameter->isRequired());
        $this->assertTrue($bodyParameter->hasSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'description' => 'The updated User resource',
                'externalDocs' => [
                    'url' => 'https://schema.org/Person',
                ],
                'required' => [
                    'familyName',
                    'givenName',
                    'username',
                    'country',
                ],
                'properties' => [
                    'familyName' => [
                        'maxLength' => 255,
                        'type' => 'string',
                    ],
                    'givenName' => [
                        'maxLength' => 255,
                        'type' => 'string',
                    ],
                    'username' => [
                        'type' => 'string',
                    ],
                    'country' => [
                        'type' => 'string',
                        'enum' => [
                            'france',
                            'royaume-unis',
                            'italie',
                        ],
                        'default' => 'france',
                        'minItems' => 1,
                        'maxItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'france',
                    ],
                    'language' => [
                        'type' => 'string',
                        'enum' => [
                            'fr',
                            'en',
                            'es',
                            'de',
                            'it',
                        ],
                        'default' => 'fr',
                        'minItems' => 1,
                        'maxItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'fr',
                    ],
                    'applications' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => [
                                'Foo',
                                'Bar',
                                'Baz',
                            ],
                        ],
                        'minItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'mobads',
                    ],
                    'roles' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => [
                                'ROLE_SUPER_ADMIN',
                                'ROLE_ADMIN',
                                'ROLE_USER',
                                'ROLE_TEST',
                            ],
                        ],
                        'minItems' => 1,
                        'maxItems' => 3,
                        'uniqueItems' => true,
                        'example' => '[\'ROLE_SUPER_ADMIN\']',
                    ],
                    'plainPassword' => [
                        'type' => 'string',
                        'format' => 'password',
                        'x-required-method' => [
                            'POST',
                        ],
                    ],
                ],
            ],
            $bodyParameter->getSchema()
        );
        $this->assertTrue($operation->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => [
                    'authorization',
                    'x-authorization',
                    'content-type',
                    'accept',
                ],
                'properties' => [
                    'authorization' => [
                        'type' => 'string',
                        'description' => 'Value for the Authorization header',
                    ],
                    'x-authorization' => [
                        'type' => 'string',
                        'description' => 'Value for the Authorization header',
                    ],
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => [
                            'application/hal+json',
                            'text/html',
                            'application/json',
                        ],
                    ],
                    'accept' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => [
                            'application/json',
                            'application/hal+json',
                            'text/html',
                            'application/problem+json',
                        ],
                    ],
                ],
            ],
            $operation->getHeadersSchema()
        );

        $this->assertTrue($operation->hasPathSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['slug'],
                'properties' => [
                    'slug' => [
                        'type' => 'string',
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
                'description' => 'The updated User resource',
                'externalDocs' => [
                    'url' => 'https://schema.org/Person',
                ],
                'required' => [
                    'familyName',
                    'givenName',
                    'username',
                    'country',
                ],
                'properties' => [
                    'familyName' => [
                        'maxLength' => 255,
                        'type' => 'string',
                    ],
                    'givenName' => [
                        'maxLength' => 255,
                        'type' => 'string',
                    ],
                    'username' => [
                        'type' => 'string',
                    ],
                    'country' => [
                        'type' => 'string',
                        'enum' => [
                            'france',
                            'royaume-unis',
                            'italie',
                        ],
                        'default' => 'france',
                        'minItems' => 1,
                        'maxItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'france',
                    ],
                    'language' => [
                        'type' => 'string',
                        'enum' => [
                            'fr',
                            'en',
                            'es',
                            'de',
                            'it',
                        ],
                        'default' => 'fr',
                        'minItems' => 1,
                        'maxItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'fr',
                    ],
                    'applications' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => [
                                'Foo',
                                'Bar',
                                'Baz',
                            ],
                        ],
                        'minItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'mobads',
                    ],
                    'roles' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => [
                                'ROLE_SUPER_ADMIN',
                                'ROLE_ADMIN',
                                'ROLE_USER',
                                'ROLE_TEST',
                            ],
                        ],
                        'minItems' => 1,
                        'maxItems' => 3,
                        'uniqueItems' => true,
                        'example' => '[\'ROLE_SUPER_ADMIN\']',
                    ],
                    'plainPassword' => [
                        'type' => 'string',
                        'format' => 'password',
                        'x-required-method' => [
                            'POST',
                        ],
                    ],
                ],
            ],
            $operation->getBodySchema()
        );

        $this->assertSame(['application/hal+json', 'text/html', 'application/json'], $operation->getContentTypes());

        $responseDefinition = $operation->getResponseDefinition($statusCode);
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $parameters = $responseDefinition->getParameters();
        $this->assertInstanceOf(Parameters::class, $parameters);

        $this->assertTrue($parameters->hasHeadersSchema());
        $this->assertSame(
            ['type' => 'object', 'required' => [], 'properties' => ['x-version' => ['type' => 'string']]],
            $parameters->getHeadersSchema()
        );

        $this->assertFalse($parameters->hasPathSchema());
        $this->assertSame([], $parameters->getPathSchema());

        $this->assertFalse($parameters->hasQueryParametersSchema());
        $this->assertSame([], $parameters->getQueryParametersSchema());

        $this->assertTrue($parameters->hasBodySchema());
        $this->assertSame(
            [
                'type' => 'object',
                'description' => '',
                'externalDocs' => [
                    'url' => 'https://schema.org/Person',
                ],
                'required' => [
                    'familyName',
                    'givenName',
                    'username',
                    'country',
                ],
                'properties' => [
                    'familyName' => [
                        'maxLength' => 255,
                        'type' => 'string',
                    ],
                    'givenName' => [
                        'maxLength' => 255,
                        'type' => 'string',
                    ],
                    'slug' => [
                        'type' => 'string',
                    ],
                    'username' => [
                        'type' => 'string',
                    ],
                    'country' => [
                        'type' => 'string',
                        'enum' => [
                            'france',
                            'royaume-unis',
                            'italie',
                        ],
                        'default' => 'france',
                        'minItems' => 1,
                        'maxItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'france',
                    ],
                    'language' => [
                        'type' => 'string',
                        'enum' => [
                            'fr',
                            'en',
                            'es',
                            'de',
                            'it',
                        ],
                        'default' => 'fr',
                        'minItems' => 1,
                        'maxItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'fr',
                    ],
                    'lastLogin' => [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                    'dateDeleted' => [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                    'deletedBy' => [
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
            $parameters->getBodySchema()
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
                'externalDocs' => [
                    'url' => 'https://schema.org/Person',
                ],
                'required' => [
                    'familyName',
                    'givenName',
                    'username',
                    'country',
                ],
                'properties' => [
                    'familyName' => [
                        'maxLength' => 255,
                        'type' => 'string',
                    ],
                    'givenName' => [
                        'maxLength' => 255,
                        'type' => 'string',
                    ],
                    'slug' => [
                        'type' => 'string',
                    ],
                    'username' => [
                        'type' => 'string',
                    ],
                    'country' => [
                        'type' => 'string',
                        'enum' => [
                            'france',
                            'royaume-unis',
                            'italie',
                        ],
                        'default' => 'france',
                        'minItems' => 1,
                        'maxItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'france',
                    ],
                    'language' => [
                        'type' => 'string',
                        'enum' => [
                            'fr',
                            'en',
                            'es',
                            'de',
                            'it',
                        ],
                        'default' => 'fr',
                        'minItems' => 1,
                        'maxItems' => 1,
                        'uniqueItems' => true,
                        'example' => 'fr',
                    ],
                    'lastLogin' => [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                    'dateDeleted' => [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                    'deletedBy' => [
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
            $bodyParameter->getSchema()
        );

        $responseDefinition = $operation->getResponseDefinition('500');
        $this->assertSame('default', $responseDefinition->getStatusCode());
        $this->assertFalse($responseDefinition->hasHeadersSchema());
        $this->assertSame([], $responseDefinition->getHeadersSchema());
        $this->assertTrue($responseDefinition->hasBodySchema());
        $this->assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'type' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'detail' => ['type' => 'string'],
                ],
            ],
            $responseDefinition->getBodySchema()
        );
    }

    public static function getValidFiles(): array
    {
        $baseFile = './tests/Fixtures/v2/%s';

        return [
            [realpath(sprintf($baseFile, 'test.yaml')), 200],
            [realpath(sprintf($baseFile, 'test.yml')), 200],
            [realpath(sprintf($baseFile, 'test.json')), 200],
            [realpath(sprintf($baseFile, 'test.yaml')), '200'],
            [realpath(sprintf($baseFile, 'test.yml')), '200'],
            [realpath(sprintf($baseFile, 'test.json')), '200'],
        ];
    }

    private function getSchema(string $file): Schema
    {
        $factory = new SwaggerSchemaFactory();

        return $factory->createSchema($file);
    }
}
