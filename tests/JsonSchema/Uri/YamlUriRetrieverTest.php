<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\JsonSchema\Uri;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\JsonSchema\Uri\YamlUriRetriever;

final class YamlUriRetrieverTest extends TestCase
{
    public function testItCanLoadAYamlFile(): void
    {
        $retriever = new YamlUriRetriever();
        $object = $retriever->retrieve(sprintf('file://%s/../../Fixtures/v2/petstore.yaml', __DIR__));

        $this->assertInstanceOf(\stdClass::class, $object);

        $object = $retriever->retrieve(sprintf('file://%s/../../Fixtures/v2/petstore.yaml', __DIR__));

        $this->assertInstanceOf(\stdClass::class, $object);
    }
}
