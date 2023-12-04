<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Validator;

use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use TwentytwoLabs\ApiValidator\Decoder\DecoderInterface;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;
use TwentytwoLabs\ApiValidator\Validator\ConstraintViolation;
use TwentytwoLabs\ApiValidator\Validator\MessageValidator;

final class MessageValidatorTest extends TestCase
{
    private Validator $validator;
    private DecoderInterface $decoder;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(Validator::class);
        $this->decoder = $this->createMock(DecoderInterface::class);
    }

    public function testShouldNotValidateRequestForGetCollectionBecauseContentTypeIsNotMatching()
    {
        $headersSchema = [
            'type' => 'object',
            'required' => ['content-type'],
            'properties' => [
                'x-uid' => ['type' => 'string'],
                'type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
                'cache-control' => ['type' => 'string'],
            ],
        ];
        $headers = [
            'Content-Type' => ['application/ld+json'],
            'X-uid' => ['114e010'],
            'Cache-Control' => ['no-cache', 'no-store', 'must-revalidate'],
        ];

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->never())->method('getUri');
        $request->expects($this->once())->method('getHeaders')->willReturn($headers);
        $request->expects($this->never())->method('getHeaderLine');
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getBody');

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(true);
        $requestDefinition->expects($this->once())->method('getHeadersSchema')->willReturn($headersSchema);

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getPathSchema');

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getQueryParametersSchema');

        $requestDefinition->expects($this->exactly(1))->method('hasBodySchema')->willReturn(false);

        $requestDefinition->expects($this->never())->method('getContentTypes');

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertInstanceOf(\stdClass::class, $value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $value = json_decode(json_encode($value), true);
            $schema = json_decode(json_encode($schema), true);

            $this->assertSame(
                [
                    'content-type' => 'application/ld+json',
                    'x-uid' => '114e010',
                    'cache-control' => 'no-cache, no-store, must-revalidate',
                ],
                $value
            );

            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => ['content-type'],
                    'properties' => [
                        'x-uid' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
                        'cache-control' => ['type' => 'string'],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(false);
        $this->validator->expects($this->once())->method('reset');
        $this->validator->expects($this->once())->method('getErrors')->willReturn([
            [
                'property' => 'content-type',
                'pointer' => '/content-type',
                'message' => 'Does not have a value in the enumeration ["application\/json"]',
                'constraint' => 'enum',
                'context' => 1,
                'enum' => ['application/json'],
            ],
        ]);

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertTrue($messageValidator->hasViolations());
        $violations = $messageValidator->getViolations();
        $this->assertIsArray($violations);
        $this->assertCount(1, $violations);
        $violation = $violations[0];
        $this->assertInstanceOf(ConstraintViolation::class, $violation);
        $this->assertSame('content-type', $violation->getProperty());
        $this->assertSame('Does not have a value in the enumeration ["application\/json"]', $violation->getMessage());
        $this->assertSame('enum', $violation->getConstraint());
        $this->assertSame('header', $violation->getLocation());
        $this->assertSame(
            [
                'property' => 'content-type',
                'message' => 'Does not have a value in the enumeration ["application\/json"]',
                'constraint' => 'enum',
                'location' => 'header',
            ],
            $violation->toArray()
        );
    }

    public function testShouldValidateRequestForGetCollection()
    {
        $guerySchema = [
            'type' => 'object',
            'required' => [],
            'properties' => [
                'force' => ['type' => 'boolean', 'default' => false],
                'page' => ['type' => 'integer', 'default' => 1],
                'itemsPerPage' => ['type' => 'integer', 'default' => 30, 'minimum' => 0],
            ],
        ];
        $query = 'page=1&itemsPerPage=10';

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->never())->method('getPath');
        $uri->expects($this->once())->method('getQuery')->willReturn($query);

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())->method('getUri')->willReturn($uri);
        $request->expects($this->never())->method('getHeaders');
        $request->expects($this->never())->method('getHeaderLine');
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getBody');

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getHeadersSchema');

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getPathSchema');

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(true);
        $requestDefinition->expects($this->once())->method('getQueryParametersSchema')->willReturn($guerySchema);

        $requestDefinition->expects($this->exactly(1))->method('hasBodySchema')->willReturn(false);

        $requestDefinition->expects($this->never())->method('getContentTypes');

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertInstanceOf(\stdClass::class, $value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $value = json_decode(json_encode($value), true);
            $schema = json_decode(json_encode($schema), true);

            $this->assertSame(['page' => 1, 'itemsPerPage' => 10], $value);
            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => [],
                    'properties' => [
                        'force' => ['type' => 'boolean', 'default' => false],
                        'page' => ['type' => 'integer', 'default' => 1],
                        'itemsPerPage' => ['type' => 'integer', 'default' => 30, 'minimum' => 0],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertFalse($messageValidator->hasViolations());
        $this->assertSame([], $messageValidator->getViolations());
    }

    public function testShouldValidateRequestForGetCollectionWithNormalizer()
    {
        $guerySchema = [
            'type' => 'object',
            'required' => [],
            'properties' => [
                'force' => ['type' => 'boolean', 'default' => false],
                'page' => ['type' => 'integer', 'default' => 1],
                'itemsPerPage' => ['type' => 'integer', 'default' => 30, 'minimum' => 0],
            ],
        ];
        $query = 'page=1&itemsPerPage=10';

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->never())->method('getPath');
        $uri->expects($this->once())->method('getQuery')->willReturn($query);

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())->method('getUri')->willReturn($uri);
        $request->expects($this->never())->method('getHeaders');
        $request->expects($this->never())->method('getHeaderLine');
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getBody');

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getHeadersSchema');

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getPathSchema');

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(true);
        $requestDefinition->expects($this->once())->method('getQueryParametersSchema')->willReturn($guerySchema);

        $requestDefinition->expects($this->exactly(1))->method('hasBodySchema')->willReturn(false);

        $requestDefinition->expects($this->never())->method('getContentTypes');

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertInstanceOf(\stdClass::class, $value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $this->assertSame(['page' => 1, 'itemsPerPage' => 10], json_decode(json_encode($value), true));
            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => [],
                    'properties' => [
                        'force' => ['type' => 'boolean', 'default' => false],
                        'page' => ['type' => 'integer', 'default' => 1],
                        'itemsPerPage' => ['type' => 'integer', 'default' => 30, 'minimum' => 0],
                    ],
                ],
                json_decode(json_encode($schema), true)
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertFalse($messageValidator->hasViolations());
        $this->assertSame([], $messageValidator->getViolations());
    }

    public function testShouldValidateRequestForGetItem()
    {
        $pathSchema = [
            'type' => 'object',
            'required' => ['id'],
            'properties' => [
                'id' => ['type' => 'string'],
            ],
        ];
        $pathTemplate = '/features/{id}';
        $path = '/features/1';

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())->method('getPath')->willReturn($path);
        $uri->expects($this->never())->method('getQuery');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())->method('getUri')->willReturn($uri);
        $request->expects($this->never())->method('getHeaders');
        $request->expects($this->never())->method('getHeaderLine');
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getBody');

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getHeadersSchema');

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(true);
        $requestDefinition->expects($this->once())->method('getPathTemplate')->willReturn($pathTemplate);
        $requestDefinition->expects($this->once())->method('getPathSchema')->willReturn($pathSchema);

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getQueryParametersSchema');

        $requestDefinition->expects($this->once())->method('hasBodySchema')->willReturn(false);

        $requestDefinition->expects($this->never())->method('getContentTypes');

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertInstanceOf(\stdClass::class, $value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $value = json_decode(json_encode($value), true);
            $schema = json_decode(json_encode($schema), true);

            $this->assertSame(['id' => '1'], $value);
            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => ['id'],
                    'properties' => [
                        'id' => ['type' => 'string'],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertFalse($messageValidator->hasViolations());
        $this->assertSame([], $messageValidator->getViolations());
    }

    public function testShouldNotValidateRequestForCreateItemBecauseContentTypeIsNotEmpty()
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->never())->method('getUri');
        $request->expects($this->never())->method('getHeaders');
        $request->expects($this->exactly(1))->method('getHeaderLine')->with('Content-Type')->willReturn('');
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getBody');

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getHeadersSchema');
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getPathSchema');

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getQueryParametersSchema');

        $requestDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $requestDefinition->expects($this->never())->method('getBodySchema');

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->never())->method('check');
        $this->validator->expects($this->never())->method('isValid');
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->never())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertTrue($messageValidator->hasViolations());
        $violations = $messageValidator->getViolations();
        $this->assertIsArray($violations);
        $this->assertCount(1, $violations);
        $violation = $violations[0];
        $this->assertInstanceOf(ConstraintViolation::class, $violation);
        $this->assertSame('Content-Type', $violation->getProperty());
        $this->assertSame('Content-Type should not be empty', $violation->getMessage());
        $this->assertSame('required', $violation->getConstraint());
        $this->assertSame('header', $violation->getLocation());
        $this->assertSame(
            [
                'property' => 'Content-Type',
                'message' => 'Content-Type should not be empty',
                'constraint' => 'required',
                'location' => 'header',
            ],
            $violation->toArray()
        );
    }

    public function testShouldNotValidateRequestForCreateItemBecauseContentTypeIsNotValidate()
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->never())->method('getUri');
        $request->expects($this->never())->method('getHeaders');
        $request->expects($this->exactly(3))->method('getHeaderLine')->with('Content-Type')->willReturn('application/ld+json; charset=utf8');
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getBody');

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getHeadersSchema');
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getPathSchema');

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getQueryParametersSchema');

        $requestDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $requestDefinition->expects($this->never())->method('getBodySchema');

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->never())->method('check');
        $this->validator->expects($this->never())->method('isValid');
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->never())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertTrue($messageValidator->hasViolations());
        $violations = $messageValidator->getViolations();
        $this->assertIsArray($violations);
        $this->assertCount(1, $violations);
        $violation = $violations[0];
        $this->assertInstanceOf(ConstraintViolation::class, $violation);
        $this->assertSame('Content-Type', $violation->getProperty());
        $this->assertSame('application/ld+json; charset=utf8 is not a supported content type, supported: application/json', $violation->getMessage());
        $this->assertSame('enum', $violation->getConstraint());
        $this->assertSame('header', $violation->getLocation());
        $this->assertSame(
            [
                'property' => 'Content-Type',
                'message' => 'application/ld+json; charset=utf8 is not a supported content type, supported: application/json',
                'constraint' => 'enum',
                'location' => 'header',
            ],
            $violation->toArray()
        );
    }

    #[DataProvider('getWriteMethod')]
    public function testShouldValidateRequestForCreateItem(string $method)
    {
        $bodySchema = [
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('{"name":"foo"}');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->never())->method('getUri');
        $request->expects($this->never())->method('getHeaders');
        $request->expects($this->exactly(3))->method('getHeaderLine')->with('Content-Type')->willReturn('application/json; charset=utf8');
        $request->expects($this->once())->method('getMethod')->willReturn($method);
        $request->expects($this->once())->method('getBody')->willReturn($stream);

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getHeadersSchema');
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getPathSchema');

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getQueryParametersSchema');

        $requestDefinition->expects($this->exactly(2))->method('hasBodySchema')->willReturn(true);
        $requestDefinition->expects($this->once())->method('getBodySchema')->willReturn($bodySchema);

        $this->decoder->expects($this->once())->method('decode')->with('{"name":"foo"}', 'json')->willReturn(['name' => 'foo']);

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertIsArray($value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $schema = json_decode(json_encode($schema), true);

            $this->assertSame(['name' => 'foo'], $value);
            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => ['name'],
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertFalse($messageValidator->hasViolations());
        $this->assertSame([], $messageValidator->getViolations());
    }

    #[DataProvider('getWriteMethod')]
    public function testShouldValidateRequestForCreateItemWithServerRequestInterface(string $method)
    {
        $bodySchema = [
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->never())->method('getUri');
        $request->expects($this->never())->method('getHeaders');
        $request->expects($this->exactly(3))->method('getHeaderLine')->with('Content-Type')->willReturn('application/json; charset=utf8');
        $request->expects($this->once())->method('getMethod')->willReturn($method);
        $request->expects($this->once())->method('getParsedBody')->willReturn(['name' => 'foo']);

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getHeadersSchema');
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getPathSchema');

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getQueryParametersSchema');

        $requestDefinition->expects($this->exactly(2))->method('hasBodySchema')->willReturn(true);
        $requestDefinition->expects($this->once())->method('getBodySchema')->willReturn($bodySchema);

        $this->decoder->expects($this->once())->method('decode')->with('{"name":"foo"}', 'json')->willReturn(['name' => 'foo']);

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertIsArray($value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $schema = json_decode(json_encode($schema), true);

            $this->assertSame(['name' => 'foo'], $value);
            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => ['name'],
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertFalse($messageValidator->hasViolations());
        $this->assertSame([], $messageValidator->getViolations());
    }

    #[DataProvider('getWriteMethod')]
    public function testShouldValidateRequestForCreateItemWithServerRequestInterfaceAndEmptyBody(string $method)
    {
        $bodySchema = [
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->never())->method('getUri');
        $request->expects($this->never())->method('getHeaders');
        $request->expects($this->exactly(3))->method('getHeaderLine')->with('Content-Type')->willReturn('application/json; charset=utf8');
        $request->expects($this->once())->method('getMethod')->willReturn($method);
        $request->expects($this->once())->method('getParsedBody')->willReturn([]);

        $requestDefinition = $this->createMock(OperationDefinition::class);

        $requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getHeadersSchema');
        $requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);

        $requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getPathTemplate');
        $requestDefinition->expects($this->never())->method('getPathSchema');

        $requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);
        $requestDefinition->expects($this->never())->method('getQueryParametersSchema');

        $requestDefinition->expects($this->exactly(2))->method('hasBodySchema')->willReturn(true);
        $requestDefinition->expects($this->once())->method('getBodySchema')->willReturn($bodySchema);

        $this->decoder->expects($this->once())->method('decode')->with('', 'json')->willReturn([]);

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertIsArray($value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $schema = json_decode(json_encode($schema), true);

            $this->assertSame([], $value);
            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => ['name'],
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateRequest($request, $requestDefinition);
        $this->assertFalse($messageValidator->hasViolations());
        $this->assertSame([], $messageValidator->getViolations());
    }

    public static function getWriteMethod(): array
    {
        return [
            ['POST'],
            ['PUT'],
            ['PATCH'],
        ];
    }

    public function testShouldValidateResponseWhenDeleteItem()
    {
        $headersSchema = [
            'type' => 'object',
            'required' => ['content-type'],
            'properties' => [
                'x-uid' => ['type' => 'string'],
                'type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
                'cache-control' => ['type' => 'string'],
            ],
        ];
        $headers = [
            'content-type' => ['application/json'],
            'x-uid' => ['b6778b4'],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(204);
        $response->expects($this->once())->method('getHeaders')->willReturn($headers);
        $response->expects($this->once())->method('getHeaderLine')->willReturn('');
        $response->expects($this->never())->method('getBody');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(true);
        $responseDefinition->expects($this->once())->method('getHeadersSchema')->willReturn($headersSchema);
        $responseDefinition->expects($this->never())->method('getContentTypes');
        $responseDefinition->expects($this->once())->method('getStatusCode')->willReturn(204);

        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(false);

        $definition = $this->createMock(OperationDefinition::class);
        $definition->expects($this->once())->method('getResponseDefinition')->with(204)->willReturn($responseDefinition);

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertInstanceOf(\stdClass::class, $value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $value = json_decode(json_encode($value), true);
            $schema = json_decode(json_encode($schema), true);

            $this->assertSame(['content-type' => 'application/json', 'x-uid' => 'b6778b4'], $value);

            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => ['content-type'],
                    'properties' => [
                        'x-uid' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
                        'cache-control' => ['type' => 'string'],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateResponse($response, $definition);
        $this->assertFalse($messageValidator->hasViolations());
        $this->assertSame([], $messageValidator->getViolations());
    }

    public function testShouldNotValidateResponseBecauseContentTypeIsEmpty()
    {
        $headersSchema = [
            'type' => 'object',
            'required' => ['content-type'],
            'properties' => [
                'x-uid' => ['type' => 'string'],
                'type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
                'cache-control' => ['type' => 'string'],
            ],
        ];
        $headers = [
            'content-type' => ['application/json'],
            'x-uid' => ['b6778b4'],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getHeaders')->willReturn($headers);
        $response->expects($this->once())->method('getHeaderLine')->willReturn('');
        $response->expects($this->never())->method('getBody');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(true);
        $responseDefinition->expects($this->once())->method('getHeadersSchema')->willReturn($headersSchema);
        $responseDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $responseDefinition->expects($this->once())->method('getStatusCode')->willReturn(200);

        $responseDefinition->expects($this->never())->method('hasBodySchema');

        $definition = $this->createMock(OperationDefinition::class);
        $definition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertInstanceOf(\stdClass::class, $value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $value = json_decode(json_encode($value), true);
            $schema = json_decode(json_encode($schema), true);

            $this->assertSame(['content-type' => 'application/json', 'x-uid' => 'b6778b4'], $value);

            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => ['content-type'],
                    'properties' => [
                        'x-uid' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
                        'cache-control' => ['type' => 'string'],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateResponse($response, $definition);
        $this->assertTrue($messageValidator->hasViolations());
        $violations = $messageValidator->getViolations();
        $this->assertIsArray($violations);
        $this->assertCount(1, $violations);
        $violation = $violations[0];
        $this->assertInstanceOf(ConstraintViolation::class, $violation);
        $this->assertSame('Content-Type', $violation->getProperty());
        $this->assertSame('Content-Type should not be empty', $violation->getMessage());
        $this->assertSame('required', $violation->getConstraint());
        $this->assertSame('header', $violation->getLocation());
        $this->assertSame(
            [
                'property' => 'Content-Type',
                'message' => 'Content-Type should not be empty',
                'constraint' => 'required',
                'location' => 'header',
            ],
            $violation->toArray()
        );
    }

    public function testShouldNotValidateResponseBecauseContentTypeIsNotValidate()
    {
        $headersSchema = [
            'type' => 'object',
            'required' => ['content-type'],
            'properties' => [
                'x-uid' => ['type' => 'string'],
                'type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
                'cache-control' => ['type' => 'string'],
            ],
        ];
        $headers = [
            'content-type' => ['application/json'],
            'x-uid' => ['b6778b4'],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getHeaders')->willReturn($headers);
        $response->expects($this->exactly(3))->method('getHeaderLine')->willReturn('application/hal+json; charset=utf8');
        $response->expects($this->never())->method('getBody');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(true);
        $responseDefinition->expects($this->once())->method('getHeadersSchema')->willReturn($headersSchema);
        $responseDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $responseDefinition->expects($this->never())->method('getStatusCode');

        $responseDefinition->expects($this->never())->method('hasBodySchema');

        $definition = $this->createMock(OperationDefinition::class);
        $definition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->decoder->expects($this->never())->method('decode');

        $this->validator->expects($this->once())->method('check')->willReturnCallback(function ($value, $schema) {
            $this->assertInstanceOf(\stdClass::class, $value);
            $this->assertInstanceOf(\stdClass::class, $schema);

            $value = json_decode(json_encode($value), true);
            $schema = json_decode(json_encode($schema), true);

            $this->assertSame(['content-type' => 'application/json', 'x-uid' => 'b6778b4'], $value);

            $this->assertSame(
                [
                    'type' => 'object',
                    'required' => ['content-type'],
                    'properties' => [
                        'x-uid' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']],
                        'cache-control' => ['type' => 'string'],
                    ],
                ],
                $schema
            );
        });
        $this->validator->expects($this->once())->method('isValid')->willReturn(true);
        $this->validator->expects($this->never())->method('getErrors');
        $this->validator->expects($this->once())->method('reset');

        $messageValidator = $this->getValidator();
        $messageValidator->validateResponse($response, $definition);
        $this->assertTrue($messageValidator->hasViolations());
        $violations = $messageValidator->getViolations();
        $this->assertIsArray($violations);
        $this->assertCount(1, $violations);
        $violation = $violations[0];
        $this->assertInstanceOf(ConstraintViolation::class, $violation);
        $this->assertSame('Content-Type', $violation->getProperty());
        $this->assertSame('application/hal+json; charset=utf8 is not a supported content type, supported: application/json', $violation->getMessage());
        $this->assertSame('enum', $violation->getConstraint());
        $this->assertSame('header', $violation->getLocation());
        $this->assertSame(
            [
                'property' => 'Content-Type',
                'message' => 'application/hal+json; charset=utf8 is not a supported content type, supported: application/json',
                'constraint' => 'enum',
                'location' => 'header',
            ],
            $violation->toArray()
        );
    }

    public function getValidator(): MessageValidator
    {
        return new MessageValidator($this->validator, $this->decoder);
    }
}
