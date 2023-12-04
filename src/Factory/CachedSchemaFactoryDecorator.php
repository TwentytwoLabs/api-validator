<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Factory;

use Psr\Cache\CacheItemPoolInterface;
use TwentytwoLabs\ApiValidator\Schema;

final class CachedSchemaFactoryDecorator implements SchemaFactoryInterface
{
    private SchemaFactoryInterface $schemaFactory;
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache, SchemaFactoryInterface $schemaFactory)
    {
        $this->cache = $cache;
        $this->schemaFactory = $schemaFactory;
    }

    public function createSchema(string $schemaFile): Schema
    {
        $cacheKey = hash('sha1', $schemaFile);
        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            $schema = $item->get();
        } else {
            $schema = $this->schemaFactory->createSchema($schemaFile);
            $this->cache->save($item->set($schema));
        }

        return $schema;
    }
}
