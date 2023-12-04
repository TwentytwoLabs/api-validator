<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Definition;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\Definition\Parameter;
use TwentytwoLabs\ApiValidator\Definition\Parameters;

final class ParametersTest extends TestCase
{
    public function testShouldBuildParametersWithoutParameters()
    {
        $parameters = new Parameters([]);

        $this->assertFalse($parameters->hasHeadersSchema());
        $this->assertSame([], $parameters->getHeadersSchema());
        $this->assertSame([], $parameters->getHeaders());

        $this->assertFalse($parameters->hasPathSchema());
        $this->assertSame([], $parameters->getPathSchema());
        $this->assertSame([], $parameters->getPath());

        $this->assertFalse($parameters->hasQueryParametersSchema());
        $this->assertSame([], $parameters->getQueryParametersSchema());
        $this->assertSame([], $parameters->getQuery());

        $this->assertFalse($parameters->hasBodySchema());
        $this->assertSame([], $parameters->getBodySchema());
        $this->assertNull($parameters->getBody());

        $this->assertNull($parameters->getByName('foo'));
    }

    public function testShouldBuildParametersForCollectionOperation()
    {
        $parameterUid = new Parameter(location: 'header', name: 'x-uid', schema: ['type' => 'string']);
        $parameterContentType = new Parameter(location: 'header', name: 'content-type', required: true, schema: ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']]);
        $parameterPage = new Parameter(location: 'query', name: 'page', schema: ['type' => 'integer', 'default' => 1]);
        $parameterItemsPerPage = new Parameter(location: 'query', name: 'itemsPerPage', schema: ['type' => 'integer', 'default' => 30]);

        $parameters = new Parameters([$parameterUid, $parameterContentType, $parameterPage, $parameterItemsPerPage]);
        $this->assertCount(4, iterator_to_array($parameters->getIterator()));
        foreach ($parameters as $name => $parameter) {
            $this->assertTrue(\in_array($name, ['x-uid', 'content-type', 'page', 'itemsPerPage']));
            $this->assertInstanceOf(Parameter::class, $parameter);
        }

        $this->assertTrue($parameters->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type'],
                'properties' => [
                    'x-uid' => ['type' => 'string'],
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $parameters->getHeadersSchema()
        );
        $this->assertSame(
            ['x-uid' => $parameterUid, 'content-type' => $parameterContentType],
            $parameters->getHeaders()
        );

        $this->assertFalse($parameters->hasPathSchema());
        $this->assertSame([], $parameters->getPathSchema());
        $this->assertSame([], $parameters->getPath());

        $this->assertTrue($parameters->hasQueryParametersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => [],
                'properties' => [
                    'page' => ['type' => 'integer', 'default' => 1],
                    'itemsPerPage' => ['type' => 'integer', 'default' => 30],
                ],
            ],
            $parameters->getQueryParametersSchema()
        );
        $this->assertSame(
            ['page' => $parameterPage, 'itemsPerPage' => $parameterItemsPerPage],
            $parameters->getQuery()
        );

        $this->assertFalse($parameters->hasBodySchema());
        $this->assertSame([], $parameters->getBodySchema());
        $this->assertNull($parameters->getBody());

        $this->assertSame($parameterItemsPerPage, $parameters->getByName('itemsPerPage'));
        $this->assertNull($parameters->getByName('enabled'));
    }

    public function testShouldBuildParametersForCreationOperation()
    {
        $parameterUid = new Parameter(location: 'header', name: 'x-uid', schema: ['type' => 'string']);
        $parameterContentType = new Parameter(location: 'header', name: 'content-type', required: true, schema: ['type' => 'string', 'default' => 'application/json', 'enum' => ['application/json']]);
        $parameterBody = new Parameter(
            location: 'body',
            name: 'body',
            required: true,
            schema: [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['avatar', 'skill']],
                    'alternativeText' => ['type' => 'string'],
                    'file' => ['type' => 'string'],
                ],
            ]
        );

        $parameters = new Parameters([$parameterUid, $parameterContentType, $parameterBody]);
        $this->assertCount(3, iterator_to_array($parameters->getIterator()));
        foreach ($parameters as $name => $parameter) {
            $this->assertTrue(\in_array($name, ['x-uid', 'content-type', 'body']));
            $this->assertInstanceOf(Parameter::class, $parameter);
        }

        $this->assertTrue($parameters->hasHeadersSchema());
        $this->assertSame(
            [
                'type' => 'object',
                'required' => ['content-type'],
                'properties' => [
                    'x-uid' => ['type' => 'string'],
                    'content-type' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => ['application/json'],
                    ],
                ],
            ],
            $parameters->getHeadersSchema()
        );
        $this->assertSame(
            ['x-uid' => $parameterUid, 'content-type' => $parameterContentType],
            $parameters->getHeaders()
        );

        $this->assertFalse($parameters->hasPathSchema());
        $this->assertSame([], $parameters->getPathSchema());
        $this->assertSame([], $parameters->getPath());

        $this->assertFalse($parameters->hasQueryParametersSchema());
        $this->assertSame([], $parameters->getQueryParametersSchema());
        $this->assertSame([], $parameters->getQuery());

        $this->assertTrue($parameters->hasBodySchema());
        $this->assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['avatar', 'skill']],
                    'alternativeText' => ['type' => 'string'],
                    'file' => ['type' => 'string'],
                ],
            ],
            $parameters->getBodySchema()
        );
        $this->assertSame($parameterBody, $parameters->getBody());

        $this->assertSame($parameterBody, $parameters->getByName('body'));
        $this->assertNull($parameters->getByName('enabled'));
    }
}
