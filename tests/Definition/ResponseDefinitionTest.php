<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Definition;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Definition\Parameter;
use TwentytwoLabs\ApiValidator\Definition\Parameters;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;

final class ResponseDefinitionTest extends TestCase
{
    #[DataProvider('getData')]
    public function testShouldValidateGetteur($statusCode)
    {
        $bodySchema = [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['title', 'type', 'file'],
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'enum' => ['avatar', 'skill']],
                        'alternativeText' => ['type' => 'string'],
                        'file' => ['type' => 'string'],
                    ],
                ],
            ],
            'application/hal+json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['title', 'type', 'file'],
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'enum' => ['avatar', 'skill']],
                        'alternativeText' => ['type' => 'string'],
                        'file' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $body = new Parameter(location: 'body', name: 'body', required: true, schema: $bodySchema);

        $responseDefinition = new ResponseDefinition($statusCode, new Parameters([$body]));
        $this->assertSame($statusCode, $responseDefinition->getStatusCode());

        $this->assertFalse($responseDefinition->hasHeadersSchema());
        $this->assertSame([], $responseDefinition->getHeadersSchema());

        $this->assertSame(['application/json', 'application/hal+json'], $responseDefinition->getContentTypes());

        $this->assertTrue($responseDefinition->hasBodySchema());
        $this->assertSame($bodySchema, $responseDefinition->getBodySchema());

        $parameters = $responseDefinition->getParameters();
        $this->assertInstanceOf(Parameters::class, $parameters);
        $this->assertFalse($parameters->hasHeadersSchema());
        $this->assertSame([], $parameters->getHeadersSchema());
        $this->assertSame([], $parameters->getHeaders());
        $this->assertFalse($parameters->hasPathSchema());
        $this->assertSame([], $parameters->getPathSchema());
        $this->assertSame([], $parameters->getPath());
        $this->assertFalse($parameters->hasQueryParametersSchema());
        $this->assertSame([], $parameters->getQueryParametersSchema());
        $this->assertSame([], $parameters->getQuery());
        $this->assertTrue($parameters->hasBodySchema());
        $this->assertSame($bodySchema, $parameters->getBodySchema());
        $this->assertSame($body, $parameters->getBody());
    }

    public static function getData(): array
    {
        return [
            ['200'],
            [200],
        ];
    }
}
