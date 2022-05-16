<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\JsonSchema\Uri;

use TwentytwoLabs\Api\JsonSchema\Uri\YamlUriRetriever;
use PHPUnit\Framework\TestCase;

/**
 * Class YamlUriRetrieverTest.
 */
class YamlUriRetrieverTest extends TestCase
{
    public function testItCanLoadAYamlFile()
    {
        $retriever = new YamlUriRetriever();
        $object = $retriever->retrieve('file://'.__DIR__.'/../../fixtures/petstore.yaml');

        $this->assertTrue(is_object($object));

        $object = $retriever->retrieve('file://'.__DIR__.'/../../fixtures/petstore.yaml');

        $this->assertTrue(is_object($object));
    }
}
