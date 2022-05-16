<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Definition;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\Api\Definition\Parameters;
use TwentytwoLabs\Api\Definition\ResponseDefinition;

/**
 * Class ResponseDefinitionTest.
 *
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class ResponseDefinitionTest extends TestCase
{
    /**
     * @dataProvider getStatusCode
     */
    public function testShouldValidateGetteur($statusCode, bool $hasBodySchema, bool $hasHeadersSchema)
    {
        $bodySchema = new \stdClass();
        $headerSchema = new \stdClass();

        $parameters = $this->createMock(Parameters::class);
        $parameters->expects($this->once())->method('hasBodySchema')->willReturn($hasBodySchema);
        $parameters->expects($this->once())->method('getBodySchema')->willReturn($bodySchema);
        $parameters->expects($this->once())->method('hasHeadersSchema')->willReturn($hasHeadersSchema);
        $parameters->expects($this->once())->method('getHeadersSchema')->willReturn($headerSchema);

        $allowedContentTypes = ['application/json', 'application/hal+json'];

        $responseDefinition = new ResponseDefinition($statusCode, $allowedContentTypes, $parameters);
        $this->assertSame($statusCode, $responseDefinition->getStatusCode());
        $this->assertSame($parameters, $responseDefinition->getParameters());
        $this->assertSame($hasBodySchema, $responseDefinition->hasBodySchema());
        $this->assertSame($bodySchema, $responseDefinition->getBodySchema());

        $this->assertSame($hasHeadersSchema, $responseDefinition->hasHeadersSchema());
        $this->assertSame($headerSchema, $responseDefinition->getHeadersSchema());

        $this->assertSame($allowedContentTypes, $responseDefinition->getContentTypes());
    }

    /**
     * @dataProvider getStatusCode
     */
    public function testShouldSerializable($statusCode)
    {
        $parameters = $this->createMock(Parameters::class);
        $parameters->expects($this->never())->method('hasBodySchema');
        $parameters->expects($this->never())->method('getBodySchema');
        $parameters->expects($this->never())->method('hasHeadersSchema');
        $parameters->expects($this->never())->method('getHeadersSchema');

        $allowedContentTypes = ['application/json', 'application/hal+json'];

        $responseDefinition = new ResponseDefinition($statusCode, $allowedContentTypes, $parameters);
        $this->assertEquals($responseDefinition, unserialize(serialize($responseDefinition)));
    }

    public function getStatusCode(): array
    {
        return [
            ['200', true, false],
            [200, true, false],
            ['200', false, false],
            [200, false, false],
            ['200', true, true],
            [200, true, true],
            ['200', false, true],
            [200, false, true],
        ];
    }
}
