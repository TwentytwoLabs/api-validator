<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use TwentytwoLabs\Api\Factory\CachedSchemaFactoryDecorator;
use TwentytwoLabs\Api\Factory\SchemaFactoryInterface;
use TwentytwoLabs\Api\Schema;

/**
 * Class CachedSchemaFactoryDecoratorTest.
 *
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class CachedSchemaFactoryDecoratorTest extends TestCase
{
    private SchemaFactoryInterface $schemaFactory;
    private CacheItemPoolInterface $cache;

    protected function setUp(): void
    {
        $this->schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
    }

    public function testShouldCreateSchema()
    {
        $schema = $this->createMock(Schema::class);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);
        $cacheItem->expects($this->never())->method('get');
        $cacheItem->expects($this->once())->method('set')->with($schema)->willReturnSelf();

        $this->cache->expects($this->once())->method('getItem')->with('7bd135e467998285daab398a98fe0eca4f3bbc7d')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save')->with($cacheItem)->willReturn(true);

        $this->schemaFactory->expects($this->once())->method('createSchema')->with('foo.yaml')->willReturn($schema);

        $factoryDecorator = $this->getFactoryDecorator();
        $factoryDecorator->createSchema('foo.yaml');
    }

    public function testShouldNotCreateSchemaBecauseItIsCache()
    {
        $schema = $this->createMock(Schema::class);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(true);
        $cacheItem->expects($this->once())->method('get')->willReturn($schema);
        $cacheItem->expects($this->never())->method('set');

        $this->cache->expects($this->once())->method('getItem')->with('7bd135e467998285daab398a98fe0eca4f3bbc7d')->willReturn($cacheItem);
        $this->cache->expects($this->never())->method('save');

        $this->schemaFactory->expects($this->never())->method('createSchema');

        $factoryDecorator = $this->getFactoryDecorator();
        $factoryDecorator->createSchema('foo.yaml');
    }

    private function getFactoryDecorator(): CachedSchemaFactoryDecorator
    {
        return new CachedSchemaFactoryDecorator($this->cache, $this->schemaFactory);
    }
}
