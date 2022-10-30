<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Factory;

use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use Symfony\Component\Yaml\Yaml;
use TwentytwoLabs\Api\Definition\Parameter;
use TwentytwoLabs\Api\Definition\RequestDefinitions;
use TwentytwoLabs\Api\Definition\ResponseDefinition;
use TwentytwoLabs\Api\JsonSchema\Uri\YamlUriRetriever;
use TwentytwoLabs\Api\Schema;

abstract class AbstractSchemaFactory implements SchemaFactoryInterface
{
    /**
     * @param string $schemaFile (must start with a scheme: file://, http://, https://, etc...)
     */
    public function createSchema(string $schemaFile): Schema
    {
        $schema = $this->resolveSchemaFile($schemaFile);

        return new Schema(
            $this->createRequestDefinitions($schema),
            $schema->basePath ?? '',
            $schema->host ?? '',
            $schema->schemes ?? ['http']
        );
    }

    protected function resolveSchemaFile(string $schemaFile): \stdClass
    {
        $extension = pathinfo($schemaFile, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'yml':
            case 'yaml':
                if (!class_exists(Yaml::class)) {
                    // @codeCoverageIgnoreStart
                    throw new \InvalidArgumentException(
                        'You need to require the "symfony/yaml" component in order to parse yml files'
                    );
                    // @codeCoverageIgnoreEnd
                }

                $uriRetriever = new YamlUriRetriever();
                break;
            case 'json':
                $uriRetriever = new UriRetriever();
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'file "%s" does not provide a supported extension choose either json, yml or yaml',
                        $schemaFile
                    )
                );
        }

        $schemaStorage = new SchemaStorage($uriRetriever, new UriResolver());

        $schema = $schemaStorage->getSchema($schemaFile);

        // JsonSchema normally defers resolution of $ref values until validation.
        // That does not work for us, because we need to have the complete schema
        // to build definitions.
        $this->expandSchemaReferences($schema, $schemaStorage);

        return $schema;
    }

    protected function expandSchemaReferences(mixed &$schema, SchemaStorage $schemaStorage): void
    {
        foreach ($schema as &$member) {
            if (is_object($member) && property_exists($member, '$ref') && is_string($member->{'$ref'})) {
                $member = $schemaStorage->resolveRef($member->{'$ref'});
            }
            if (is_object($member) || is_array($member)) {
                $this->expandSchemaReferences($member, $schemaStorage);
            }
        }
    }

    abstract protected function createRequestDefinitions(\stdClass $schema): RequestDefinitions;

    abstract protected function createResponseDefinition(
        int|string $statusCode,
        array $allowedContentTypes,
        \stdClass $response
    ): ResponseDefinition;
}
