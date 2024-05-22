<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Factory;

use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinitions;
use TwentytwoLabs\ApiValidator\Definition\Parameter;
use TwentytwoLabs\ApiValidator\JsonSchema\Uri\YamlUriRetriever;
use TwentytwoLabs\ApiValidator\Schema;

abstract class AbstractSchemaFactory implements SchemaFactoryInterface
{
    /**
     * @param string $schemaFile (must start with a scheme: file://, http://, https://, etc...)
     */
    public function createSchema(string $schemaFile): Schema
    {
        return new Schema($this->createOperationDefinitions($this->resolveSchemaFile($schemaFile)));
    }

    private function resolveSchemaFile(string $schemaFile): array
    {
        $extension = pathinfo($schemaFile, PATHINFO_EXTENSION);

        $uriRetriever = match ($extension) {
            'yml', 'yaml' => new YamlUriRetriever(),
            'json' => new UriRetriever(),
            default => throw new \InvalidArgumentException(sprintf(
                'file "%s" does not provide a supported extension choose either json, yml or yaml',
                $schemaFile
            )),
        };

        $schemaStorage = new SchemaStorage($uriRetriever, new UriResolver());

        $schema = json_decode(json_encode($schemaStorage->getSchema($schemaFile)), true);

        $this->expandSchemaReferences($schema, $schemaStorage);

        return $schema;
    }

    private function expandSchemaReferences(mixed &$schema, SchemaStorage $schemaStorage): void
    {
        foreach ($schema as &$member) {
            if (is_array($member) && array_key_exists('$ref', $member) && is_string($member['$ref'])) {
                $member = json_decode(json_encode($schemaStorage->resolveRef($member['$ref'])), true);
            }
            if (is_array($member)) {
                $this->expandSchemaReferences($member, $schemaStorage);
            }
        }
    }

    protected function createParameter(array $parameter): Parameter
    {
        $name = $parameter['name'];
        $schema = $parameter['schema'] ?? [];
        $required = $parameter['required'] ?? false;
        $location = $parameter['in'];

        unset($parameter['in'], $parameter['required'], $parameter['name'], $parameter['schema']);

        foreach ($parameter as $key => $value) {
            $schema[$key] = $value;
        }

        return new Parameter($location, $name, $required, $schema);
    }

    abstract protected function createOperationDefinitions(array $schema): OperationDefinitions;
}
