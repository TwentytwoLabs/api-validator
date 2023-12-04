<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Factory;

use TwentytwoLabs\ApiValidator\Schema;

interface SchemaFactoryInterface
{
    public function createSchema(string $schemaFile): Schema;
}
