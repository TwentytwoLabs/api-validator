<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\JsonSchema\Uri;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiValidator\JsonSchema\Uri\YamlUriRetriever;

final class YamlUriRetrieverTest extends TestCase
{
    public function testItCanLoadAYamlFile()
    {
        $retriever = new YamlUriRetriever();
        $object = $retriever->retrieve('file://'.__DIR__.'/../../Fixtures/v2/petstore.yaml');

        $this->assertTrue(is_object($object));

        $object = $retriever->retrieve('file://'.__DIR__.'/../../Fixtures/v2/petstore.yaml');

        $this->assertTrue(is_object($object));
    }
}
