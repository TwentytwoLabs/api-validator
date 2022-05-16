<?php

namespace TwentytwoLabs\Api\Factory;

use TwentytwoLabs\Api\Schema;

interface SchemaFactory
{
    /**
     * Create a Schema definition from an API definition.
     *
     * Schema file must start with a scheme: file:// or http:// or https://
     */
    public function createSchema(string $schemaFile): Schema;
}
