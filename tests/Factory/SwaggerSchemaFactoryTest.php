<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Factory;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Definition\Parameter;
use TwentytwoLabs\Api\Definition\Parameters;
use TwentytwoLabs\Api\Definition\RequestDefinition;
use TwentytwoLabs\Api\Definition\ResponseDefinition;
use TwentytwoLabs\Api\Factory\SwaggerSchemaFactory;
use TwentytwoLabs\Api\Schema;

/**
 * Class SwaggerSchemaFactoryTest.
 */
class SwaggerSchemaFactoryTest extends TestCase
{
    public function testShouldCreateASchemaFromAJsonFile()
    {
        $schema = $this->getPetStoreSchemaJson();

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertSame('/v2', $schema->getBasePath());
        $this->assertSame('petstore.swagger.io', $schema->getHost());
        $this->assertSame(['https', 'http'], $schema->getSchemes());

        $requestDefinition = $schema->getRequestDefinitions()->getRequestDefinition('addPet');

        $this->assertSame('POST', $requestDefinition->getMethod());
        $this->assertSame(['application/json', 'application/xml'], $requestDefinition->getContentTypes());
        $this->assertTrue($requestDefinition->hasHeadersSchema());
        $this->assertSame(
            '{"type":"object","required":[],"properties":{"api_key":{"type":"string"}}}',
            json_encode($requestDefinition->getHeadersSchema())
        );
        $this->assertFalse($requestDefinition->hasPathSchema());
        $this->assertTrue($requestDefinition->hasBodySchema());
        $this->assertFalse($requestDefinition->hasQueryParametersSchema());
        $this->assertSame('addPet', $requestDefinition->getOperationId());
        $this->assertSame('/v2/pet', $requestDefinition->getPathTemplate());
        $this->assertInstanceOf(Parameters::class, $requestDefinition->getRequestParameters());
        $this->assertSame(['application/json', 'application/xml'], $requestDefinition->getContentTypes());
        $bodySchema = $requestDefinition->getBodySchema();
        $this->assertFalse(isset($bodySchema->{'$ref'}));
    }

    public function testShouldCreateASchemaFromAYamlFile()
    {
        $schema = $this->getPetStoreSchemaYaml();

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertSame('/v2', $schema->getBasePath());
        $this->assertSame('petstore.swagger.io', $schema->getHost());
        $this->assertSame(['https', 'http'], $schema->getSchemes());

        $requestDefinition = $schema->getRequestDefinitions()->getRequestDefinition('addPet');
        $this->assertSame('POST', $requestDefinition->getMethod());
        $this->assertSame('addPet', $requestDefinition->getOperationId());
        $this->assertSame('/v2/pet', $requestDefinition->getPathTemplate());
        $this->assertInstanceOf(Parameters::class, $requestDefinition->getRequestParameters());
        $this->assertSame(['application/json', 'application/xml'], $requestDefinition->getContentTypes());
        $bodySchema = $requestDefinition->getBodySchema();
        $this->assertFalse(isset($bodySchema->{'$ref'}));
    }

    public function testShouldCreateASchemaFromAYmlFile()
    {
        $schema = $this->getPetStoreSchemaYml();

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertSame('/v2', $schema->getBasePath());
        $this->assertSame('petstore.swagger.io', $schema->getHost());
        $this->assertSame(['http'], $schema->getSchemes());

        $requestDefinition = $schema->getRequestDefinitions()->getRequestDefinition('addPet');
        $this->assertSame('POST', $requestDefinition->getMethod());
        $this->assertSame('addPet', $requestDefinition->getOperationId());
        $this->assertSame('/v2/pet', $requestDefinition->getPathTemplate());
        $this->assertInstanceOf(Parameters::class, $requestDefinition->getRequestParameters());
        $this->assertSame(['application/json', 'application/xml'], $requestDefinition->getContentTypes());
        $bodySchema = $requestDefinition->getBodySchema();
        $this->assertFalse(isset($bodySchema->{'$ref'}));
    }

    public function testShouldThrowAnExceptionWhenTheSchemaFileIsNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/does not provide a supported extension/');

        $unsupportedFile = 'file://'.dirname(__DIR__).'/fixtures/petstore.txt';

        (new SwaggerSchemaFactory())->createSchema($unsupportedFile);
    }

    public function testShouldHaveSchemaProperties()
    {
        $schema = $this->getPetStoreSchemaJson();

        $this->assertSame('petstore.swagger.io', $schema->getHost());
        $this->assertSame('/v2', $schema->getBasePath());
        $this->assertSame(['https', 'http'], $schema->getSchemes());
    }

    public function testShouldThrowAnExceptionWhenAnOperationDoesNotProvideAnId()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to provide an operationId for GET /something');

        $this->getSchemaFromFile('operation-without-an-id.json');
    }

    public function testShouldThrowAnExceptionWhenAnOperationDoesNotProvideResponses()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to specify at least one response for GET /something');

        $this->getSchemaFromFile('operation-without-responses.json');
    }

    public function testShouldSupportAnOperationWithoutParameters()
    {
        $schema = $this->getSchemaFromFile('operation-without-parameters.json');
        $definition = $schema->getRequestDefinition('getSomething');

        $this->assertFalse($definition->hasHeadersSchema());
        $this->assertFalse($definition->hasBodySchema());
        $this->assertFalse($definition->hasQueryParametersSchema());
    }

    public function testShouldCreateARequestDefinition()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestDefinition = $schema->getRequestDefinition('findPetsByStatus');

        $this->assertInstanceOf(RequestDefinition::class, $requestDefinition);
        $this->assertSame('GET', $requestDefinition->getMethod());
        $this->assertSame('findPetsByStatus', $requestDefinition->getOperationId());
        $this->assertSame('/v2/pet/findByStatus', $requestDefinition->getPathTemplate());
        $this->assertSame([], $requestDefinition->getContentTypes());
        $this->assertInstanceOf(Parameters::class, $requestDefinition->getRequestParameters());

        $responseDefinition = $requestDefinition->getResponseDefinition(200);
        $this->assertInstanceOf(ResponseDefinition::class, $responseDefinition);
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertSame(['application/xml', 'application/json'], $responseDefinition->getContentTypes());
        $this->assertInstanceOf(Parameter::class, $responseDefinition->getParameters()->getBody());

        $body = $responseDefinition->getParameters()->getBody();
        $this->assertSame('body', $body->getLocation());
        $this->assertSame('body', $body->getName());
        $this->assertTrue($body->isRequired());
        $this->assertInstanceOf(\stdClass::class, $body->getSchema());
        $this->assertSame('array', $body->getSchema()->type);

        $this->assertInstanceOf(ResponseDefinition::class, $requestDefinition->getResponseDefinition(400));
    }

    public function testShouldCreateARequestBodyParameter()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestParameters = $schema->getRequestDefinition('addPet')->getRequestParameters();

        $this->assertInstanceOf(Parameters::class, $requestParameters);
        $this->assertInstanceOf(Parameter::class, $requestParameters->getBody());
        $this->assertTrue($requestParameters->hasBodySchema());
        $this->assertTrue(is_object($requestParameters->getBodySchema()));
    }

    public function testShouldCreateRequestPath()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestParameters = $schema->getRequestDefinition('getPetById')->getRequestParameters();

        $this->assertThat($requestParameters->getPath(), $this->containsOnlyInstancesOf(Parameter::class));
    }

    public function testShouldCreateRequestQueryParameters()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestParameters = $schema->getRequestDefinition('findPetsByStatus')->getRequestParameters();

        $this->assertThat($requestParameters->getQuery(), $this->containsOnlyInstancesOf(Parameter::class));
        $this->assertTrue(is_object($requestParameters->getQueryParametersSchema()));
        $queryParametersSchema = $requestParameters->getQueryParametersSchema();
        $this->assertTrue(isset($queryParametersSchema->properties->status));
        $this->assertTrue(is_object($queryParametersSchema->properties->status));
    }

    public function testShouldCreateRequestHeadersParameter()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestParameters = $schema->getRequestDefinition('deletePet')->getRequestParameters();

        $this->assertThat($requestParameters->getHeaders(), $this->containsOnlyInstancesOf(Parameter::class));
        $this->assertTrue($requestParameters->hasHeadersSchema());
        $this->assertTrue(is_object($requestParameters->getHeadersSchema()));

        $headers = $requestParameters->getHeaders();
        $this->assertCount(1, $headers);
        $this->assertArrayHasKey('api_key', $headers);

        $apiKey = $headers['api_key'];
        $this->assertSame('header', $apiKey->getLocation());
        $this->assertSame('api_key', $apiKey->getName());
        $this->assertFalse($apiKey->isRequired());
        $this->assertNotNull($apiKey->getSchema());
    }

    public function testShouldCreateAResponseDefinition()
    {
        $schema = $this->getPetStoreSchemaJson();

        $responseDefinition = $schema->getRequestDefinition('getPetById')->getResponseDefinition(200);

        $this->assertInstanceOf(ResponseDefinition::class, $responseDefinition);
        $this->assertFalse($responseDefinition->hasHeadersSchema());
        $this->assertTrue($responseDefinition->hasBodySchema());
        $this->assertTrue(is_object($responseDefinition->getBodySchema()));
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertContains('application/json', $responseDefinition->getContentTypes());
    }

    public function testShouldCreateAResponseDefinitionWithHeaders()
    {
        $schema = $this->getPetStoreSchemaYaml();

        $responseDefinition = $schema->getRequestDefinition('loginUser')->getResponseDefinition(200);

        $this->assertInstanceOf(ResponseDefinition::class, $responseDefinition);
        $this->assertTrue($responseDefinition->hasHeadersSchema());
        $this->assertTrue($responseDefinition->hasBodySchema());
        $this->assertTrue(is_object($responseDefinition->getBodySchema()));
        $this->assertSame(200, $responseDefinition->getStatusCode());
        $this->assertContains('application/json', $responseDefinition->getContentTypes());
        $this->assertTrue($responseDefinition->hasHeadersSchema());

        $headerSchema = '{"type":"object","required":["X-Rate-Limit","X-Expires-After"],"properties":{"X-Rate-Limit":{"type":"integer","format":"int32","description":"calls per hour allowed by the user"},"X-Expires-After":{"type":"string","format":"date-time","description":"date in UTC when token expires"}}}';

        $this->assertSame($headerSchema, json_encode($responseDefinition->getHeadersSchema()));
    }

    public function testShouldUseTheSchemaDefaultConsumesPropertyWhenNotProvidedByAnOperation()
    {
        $schema = $this->getSchemaFromFile('schema-with-default-consumes-and-produces-properties.json');
        $definition = $schema->getRequestDefinition('postSomething');

        $this->assertContains('application/json', $definition->getContentTypes());
    }

    public function testShouldUseTheSchemaDefaultProducesPropertyWhenNotProvidedByAnOperationResponse()
    {
        $schema = $this->getSchemaFromFile('schema-with-default-consumes-and-produces-properties.json');
        $responseDefinition = $schema
            ->getRequestDefinition('postSomething')
            ->getResponseDefinition(201);

        $this->assertContains('application/json', $responseDefinition->getContentTypes());
    }

    /**
     * @dataProvider getGuessableContentTypes
     */
    public function testShouldGuessTheContentTypeFromRequestParameters($operationId, $expectedContentType)
    {
        $schema = $this->getSchemaFromFile('request-without-content-types.json');

        $definition = $schema->getRequestDefinition($operationId);

        $this->assertContains($expectedContentType, $definition->getContentTypes());
    }

    public function getGuessableContentTypes(): array
    {
        return [
            'body' => [
                'operationId' => 'postBodyWithoutAContentType',
                'contentType' => 'application/json',
            ],
            'formData' => [
                'operationId' => 'postFromDataWithoutAContentType',
                'contentType' => 'application/x-www-form-urlencoded',
            ],
        ];
    }

    public function testShouldFailWhenTryingToGuessTheContentTypeFromARequestWithMultipleBodyLocations()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Parameters cannot have body and formData locations at the same time in /post/with-conflicting-locations');

        $schemaFile = 'file://'.dirname(__DIR__).'/fixtures/request-with-conflicting-locations.json';
        (new SwaggerSchemaFactory())->createSchema($schemaFile);
    }

    private function getPetStoreSchemaJson(): Schema
    {
        return $this->getSchemaFromFile('petstore.json');
    }

    private function getPetStoreSchemaYaml(): Schema
    {
        return $this->getSchemaFromFile('petstore.yaml');
    }

    private function getPetStoreSchemaYml(): Schema
    {
        return $this->getSchemaFromFile('petstore.yml');
    }

    private function getSchemaFromFile(string $name): Schema
    {
        $schemaFile = 'file://'.dirname(__DIR__).'/fixtures/'.$name;
        $factory = new SwaggerSchemaFactory();

        return $factory->createSchema($schemaFile);
    }
}
