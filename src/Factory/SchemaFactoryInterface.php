<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Factory;

use TwentytwoLabs\Api\Schema;

/**
 * Interface SchemaFactoryInterface.
 */
interface SchemaFactoryInterface
{
    public function createSchema(string $schemaFile): Schema;
}
