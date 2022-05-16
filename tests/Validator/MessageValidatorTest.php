<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Validator;

use JsonSchema\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use TwentytwoLabs\Api\Decoder\DecoderInterface;
use TwentytwoLabs\Api\Definition\RequestDefinition;
use TwentytwoLabs\Api\Definition\ResponseDefinition;
use TwentytwoLabs\Api\Validator\ConstraintViolation;
use TwentytwoLabs\Api\Validator\MessageValidator;

/**
 * Class RequestValidatorTest.
 */
class MessageValidatorTest extends TestCase
{
    private Validator $validator;
    private DecoderInterface $decoder;
    private RequestInterface $request;
    private RequestDefinition $requestDefinition;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(Validator::class);
        $this->decoder = $this->createMock(DecoderInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->requestDefinition = $this->createMock(RequestDefinition::class);
    }

    public function testShouldValidateRequestWithoutBodySchema()
    {
        $this->request->expects($this->never())->method('getHeaderLine');
        $this->request->expects($this->never())->method('getMethod');

        $this->request->expects($this->never())->method('getBody');

        $this->requestDefinition->expects($this->once())->method('hasBodySchema')->willReturn(false);
        $this->requestDefinition->expects($this->never())->method('getBodySchema');
        $this->requestDefinition->expects($this->never())->method('getContentTypes');
        $this->requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $this->requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $this->requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);

        $this->decoder->expects($this->never())->method('decode');
        $this->validator->expects($this->never())->method('isValid');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateRequest($this->request, $this->requestDefinition);
    }

    public function testShouldValidateRequestWithContentTypeInvalid()
    {
        $this->request->expects($this->exactly(2))->method('getHeaderLine')->willReturn('application/hal+json');
        $this->request->expects($this->never())->method('getMethod');
        $this->request->expects($this->never())->method('getBody');

        $this->requestDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $this->requestDefinition->expects($this->never())->method('getBodySchema');
        $this->requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $this->requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $this->requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $this->requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);

        $this->decoder->expects($this->never())->method('decode');
        $this->validator->expects($this->never())->method('isValid');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateRequest($this->request, $this->requestDefinition);
    }

    public function testShouldValidateRequestWithGetMethod()
    {
        $this->request->expects($this->once())->method('getHeaderLine')->willReturn('application/json');
        $this->request->expects($this->once())->method('getMethod')->willReturn('GET');
        $this->request->expects($this->never())->method('getBody');

        $this->requestDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $this->requestDefinition->expects($this->never())->method('getBodySchema');
        $this->requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $this->requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $this->requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $this->requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);

        $this->decoder->expects($this->never())->method('decode');
        $this->validator->expects($this->never())->method('isValid');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateRequest($this->request, $this->requestDefinition);
    }

    /**
     * @dataProvider dataProviderTestShouldValidateRequestWithMethods
     */
    public function testShouldValidateRequestWithMethods(string $method)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('');

        $this->request->expects($this->once())->method('getHeaderLine')->willReturn('application/json');
        $this->request->expects($this->once())->method('getMethod')->willReturn($method);
        $this->request->expects($this->once())->method('getBody')->willReturn($stream);

        $this->requestDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $this->requestDefinition->expects($this->never())->method('getBodySchema');
        $this->requestDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $this->requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);
        $this->requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(false);
        $this->requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(false);

        $this->decoder->expects($this->never())->method('decode');
        $this->validator->expects($this->never())->method('isValid');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateRequest($this->request, $this->requestDefinition);
    }

    public function dataProviderTestShouldValidateRequestWithMethods(): array
    {
        return [
            ['PUT'],
            ['PATCH'],
            ['POST'],
        ];
    }

    public function testShouldValidateResponseWithOutBodySchema()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->never())->method('getHeaderLine');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(false);
        $responseDefinition->expects($this->never())->method('getContentTypes');
        $responseDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);

        $this->requestDefinition->expects($this->once())->method('getResponseDefinition')->willReturn($responseDefinition);

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateResponse($response, $this->requestDefinition);
    }

    public function testShouldValidateResponseWithContentTypeInvalid()
    {
        /** @var ResponseInterface|MockObject */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->exactly(2))->method('getHeaderLine')->with('Content-Type')->willReturn('application/hal+json');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $responseDefinition->expects($this->never())->method('getBodySchema');
        $responseDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $responseDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);

        $this->requestDefinition->expects($this->once())->method('getResponseDefinition')->willReturn($responseDefinition);

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateResponse($response, $this->requestDefinition);
    }

    public function testShouldValidateResponse()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('');

        /** @var ResponseInterface|MockObject */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $responseDefinition->expects($this->never())->method('getBodySchema');
        $responseDefinition->expects($this->once())->method('getContentTypes')->willReturn(['application/json']);
        $responseDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(false);

        $this->requestDefinition->expects($this->once())->method('getResponseDefinition')->willReturn($responseDefinition);

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateResponse($response, $this->requestDefinition);
    }

    /**
     * @dataProvider dataProviderTestShouldValidateHeaderOfRequest
     */
    public function testShouldValidateHeaders(array $headers, ?\stdClass $headersSchema, array $errors)
    {
        $this->request->expects($this->exactly(null === $headersSchema ? 0 : 1))->method('getHeaders')->willReturn($headers);

        $this->requestDefinition->expects($this->once())->method('hasHeadersSchema')->willReturn(null !== $headersSchema);
        $this->requestDefinition->expects($this->exactly(null === $headersSchema ? 0 : 1))->method('getHeadersSchema')->willReturn($headersSchema);

        $this->validator
            ->expects($this->exactly(null === $headersSchema ? 0 : 1))
            ->method('coerce')
            ->with(json_decode(empty($headers) ? '{}' : '{"0":"application\/json"}'), $this->isInstanceOf(\stdClass::class))
        ;
        $this->validator->expects($this->exactly(null === $headersSchema ? 0 : 1))->method('isValid')->willReturn(empty($errors));
        $this->validator->expects($this->exactly(empty($errors) ? 0 : 1))->method('getErrors')->willReturn($errors);
        $this->validator->expects($this->exactly(null === $headersSchema ? 0 : 1))->method('reset');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateHeaders($this->request, $this->requestDefinition);
        $this->assertSame(!empty($errors), $messageValidator->hasViolations());
    }

    public function dataProviderTestShouldValidateHeaderOfRequest(): array
    {
        return [
            [
                [],
                null,
                [],
            ],
            [
                [['Content-Type' => 'application/json']],
                null,
                [],
            ],
            [
                [['Content-Type' => 'application/json'], ['X-FOO' => 'bar']],
                null,
                [],
            ],
            [
                [['CONTENT-TYPE' => 'application/json']],
                null,
                [],
            ],
            [
                [],
                json_decode('{"type":"object","required":[],"properties":{"Content-Type":{"type":"string"}}}'),
                [],
            ],
            [
                [['Content-Type' => 'application/json']],
                json_decode('{"type":"object","required":[],"properties":{"foo":{"type":"string"}}}'),
                [],
            ],
            [
                [['Content-Type' => 'application/json']],
                json_decode('{"type":"object","required":[],"properties":{"foo":{"type":"string"}}}'),
                [
                    [
                        'property' => 'foo',
                        'message' => 'Foo not found',
                        'constraint' => 'bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTestShouldValidateBody
     */
    public function testShouldValidateBody(string $body, ?\stdClass $bodyDefinition, array $errors)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn($body);

        $this->request->expects($this->once())->method('getBody')->willReturn($stream);
        $this->request->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('getHeaderLine')->willReturn('application/json');

        $this->decoder->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('decode')->willReturn(json_decode($body));

        $this->requestDefinition->expects($this->exactly('' !== $body ? 1 : 0))->method('hasBodySchema')->willReturn(null !== $bodyDefinition);
        $this->requestDefinition->expects($this->exactly(null !== $bodyDefinition ? 1 : 0))->method('getBodySchema')->willReturn($bodyDefinition);

        $this->validator->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('coerce');
        $this->validator->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('isValid')->willReturn(empty($errors));
        $this->validator->expects($this->exactly(!empty($errors) ? 1 : 0))->method('getErrors')->willReturn($errors);
        $this->validator->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('reset');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateMessageBody($this->request, $this->requestDefinition);
        $this->assertSame(!empty($errors), $messageValidator->hasViolations());
    }

    /**
     * @dataProvider dataProviderTestShouldValidateBody
     */
    public function testShouldValidateBodyWithServerRequest(string $body, ?\stdClass $bodyDefinition, array $errors)
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getParsedBody')->willReturn(empty($body) ? [] : json_decode($body));
        $request->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('getHeaderLine')->willReturn('application/json');

        $this->decoder->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('decode')->willReturn(json_decode($body));

        $this->requestDefinition->expects($this->exactly(!empty($body) ? 1 : 0))->method('hasBodySchema')->willReturn(null !== $bodyDefinition);
        $this->requestDefinition->expects($this->exactly(null !== $bodyDefinition ? 1 : 0))->method('getBodySchema')->willReturn($bodyDefinition);

        $this->validator->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('coerce');
        $this->validator->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('isValid')->willReturn(empty($errors));
        $this->validator->expects($this->exactly(!empty($errors) ? 1 : 0))->method('getErrors')->willReturn($errors);
        $this->validator->expects($this->exactly('' !== $body && null !== $bodyDefinition ? 1 : 0))->method('reset');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateMessageBody($request, $this->requestDefinition);
        $this->assertSame(!empty($errors), $messageValidator->hasViolations());
    }

    public function dataProviderTestShouldValidateBody(): array
    {
        return [
            [
                '',
                null,
                [],
            ],
            [
                '{"token":"__TOKEN__"}',
                json_decode('{"type":"object","items":{"type":"object","properties":{"token":{"type":"string"}}}}'),
                [
                    [
                        'property' => 'foo',
                        'message' => 'Foo not found',
                        'constraint' => 'bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTestShouldValidateContentType
     */
    public function testShouldValidateContentType(bool $isValid, string $contentType, array $contentTypeDefinition, ?array $error)
    {
        $this->request
            ->expects($isValid || '' === $contentType ? $this->once() : $this->exactly(2))
            ->method('getHeaderLine')
            ->willReturn($contentType)
        ;
        $this->requestDefinition->expects($this->once())->method('getContentTypes')->willReturn($contentTypeDefinition);

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $this->assertSame($isValid, $messageValidator->validateContentType($this->request, $this->requestDefinition));
        if (!$isValid) {
            $this->assertNotEmpty($messageValidator->getViolations());
            $this->assertCount(1, $messageValidator->getViolations());
            foreach ($messageValidator->getViolations() as $violation) {
                $this->assertInstanceOf(ConstraintViolation::class, $violation);
                $this->assertSame('Content-Type', $violation->getProperty());
                $this->assertSame($error['message'], $violation->getMessage());
                $this->assertSame($error['constraint'], $violation->getConstraint());
                $this->assertSame('header', $violation->getLocation());
            }
        }
    }

    public function dataProviderTestShouldValidateContentType(): array
    {
        return [
            [
                true,
                'application/json',
                ['application/json'],
                null,
            ],
            [
                false,
                'application/hal+json',
                ['application/json'],
                [
                    'message' => 'application/hal+json is not a supported content type, supported: application/json',
                    'constraint' => 'enum',
                ],
            ],
            [
                false,
                '',
                ['application/json'],
                ['message' => 'Content-Type should not be empty', 'constraint' => 'required'],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTestShouldValidatePath
     *
     * @param \stdClass $pathSchema
     */
    public function testShouldValidatePath(?\stdClass $pathSchema, string $path, array $errors)
    {
        $uri = $this->createMock(UriInterface::class);
        $uri
            ->expects(null !== $pathSchema ? $this->once() : $this->never())
            ->method('getPath')
            ->willReturn($path)
        ;

        $this->request
            ->expects(null !== $pathSchema ? $this->once() : $this->never())
            ->method('getUri')
            ->willReturn($uri)
        ;

        $this->requestDefinition->expects($this->once())->method('hasPathSchema')->willReturn(null !== $pathSchema);
        $this->requestDefinition
            ->expects(null !== $pathSchema ? $this->once() : $this->never())
            ->method('getPathTemplate')
            ->willReturn($path)
        ;
        $this->requestDefinition
            ->expects(null !== $pathSchema ? $this->once() : $this->never())
            ->method('getPathSchema')
            ->willReturn($pathSchema)
        ;

        $this->validator
            ->expects(null !== $pathSchema ? $this->once() : $this->never())
            ->method('coerce')
            ->with($this->isInstanceOf(\stdClass::class), $this->isInstanceOf(\stdClass::class))
        ;
        $this->validator
            ->expects(null !== $pathSchema ? $this->once() : $this->never())
            ->method('isValid')
            ->willReturn(empty($errors))
        ;
        $this->validator->expects($this->exactly(!empty($errors) ? 1 : 0))->method('getErrors')->willReturn($errors);
        $this->validator->expects(null !== $pathSchema ? $this->once() : $this->never())->method('reset');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validatePath($this->request, $this->requestDefinition);
        $this->assertSame(!empty($errors), $messageValidator->hasViolations());
    }

    public function dataProviderTestShouldValidatePath(): array
    {
        return [
            [
                null,
                '',
                [],
            ],
            [
                json_decode('{"type":"object","required":["id"],"properties":{"id":{"type":"string"}}}'),
                '/celebrities/{id}',
                [],
            ],
            [
                json_decode('{"type":"object","required":["id"],"properties":{"id":{"type":"string"}}}'),
                '/celebrities/{id}',
                [
                    [
                        'property' => 'id',
                        'message' => 'id not found',
                        'constraint' => 'required',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderTestShouldValidateQueryParameters
     */
    public function testShouldValidateQueryParameters(?\stdClass $queryParametersSchema, string $query, array $errors, \stdClass $queryParameters)
    {
        $uri = $this->createMock(UriInterface::class);
        $uri
            ->expects(null !== $queryParametersSchema ? $this->once() : $this->never())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $this->request
            ->expects(null !== $queryParametersSchema ? $this->once() : $this->never())
            ->method('getUri')
            ->willReturn($uri)
        ;
        $this->requestDefinition->expects($this->once())->method('hasQueryParametersSchema')->willReturn(null !== $queryParametersSchema);
        $this->requestDefinition
            ->expects(null !== $queryParametersSchema ? $this->once() : $this->never())
            ->method('getQueryParametersSchema')
            ->willReturn($queryParametersSchema)
        ;

        $this->validator
            ->expects(null !== $queryParametersSchema ? $this->once() : $this->never())
            ->method('coerce')
            ->with($queryParameters, $this->isInstanceOf(\stdClass::class))
        ;
        $this->validator
            ->expects(null !== $queryParametersSchema ? $this->once() : $this->never())
            ->method('isValid')
            ->willReturn(empty($errors))
        ;
        $this->validator->expects($this->exactly(!empty($errors) ? 1 : 0))->method('getErrors')->willReturn($errors);
        $this->validator->expects(null !== $queryParametersSchema ? $this->once() : $this->never())->method('reset');

        $messageValidator = new MessageValidator($this->validator, $this->decoder);
        $messageValidator->validateQueryParameters($this->request, $this->requestDefinition);
        $this->assertSame(!empty($errors), $messageValidator->hasViolations());
    }

    public function dataProviderTestShouldValidateQueryParameters(): array
    {
        return [
            [
                null,
                '',
                [],
                json_decode('{}'),
            ],
            [
                json_decode('{"type":"object","required":[],"properties":{"order[nickName]":{"type":"string"},"order[score]":{"type":"string"},"order[dateCreated]":{"type":"string"},"order[dateModified]":{"type":"string"},"page":{"type":"integer","description":"The collection page number"}}}'),
                '',
                [],
                json_decode('{}'),
            ],
            [
                json_decode('{"type":"object","required":[],"properties":{"order[nickName]":{"type":"string"},"order[score]":{"type":"string"},"order[dateCreated]":{"type":"string"},"order[dateModified]":{"type":"string"},"page":{"type":"integer","description":"The collection page number"}}}'),
                'page=2',
                [],
                json_decode('{"page":2}'),
            ],
            [
                json_decode('{"type":"object","required":[],"properties":{"order[nickName]":{"type":"string"},"order[score]":{"type":"string"},"order[dateCreated]":{"type":"string"},"order[dateModified]":{"type":"string"},"page":{"type":"integer","description":"The collection page number"}}}'),
                'page=2&order%5BnickName%5D=asc',
                [],
                json_decode('{"page":2,"order%5BnickName%5D":"asc"}'),
            ],
            [
                json_decode('{"type":"object","required":[],"properties":{"order[nickName]":{"type":"string"},"order[score]":{"type":"string"},"order[dateCreated]":{"type":"string"},"order[dateModified]":{"type":"string"},"page":{"type":"integer","description":"The collection page number"}}}'),
                'page=bfdb&order%5BnickName%5D=asc',
                [
                    [
                        'property' => 'page',
                        'message' => 'String value found, but an integer is required',
                        'constraint' => 'type',
                    ],
                ],
                json_decode('{"page":"bfdb","order%5BnickName%5D":"asc"}'),
            ],
        ];
    }
}
