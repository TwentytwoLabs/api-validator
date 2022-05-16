<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Factory;

use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use Symfony\Component\Yaml\Yaml;
use TwentytwoLabs\Api\Definition\Parameter;
use TwentytwoLabs\Api\Definition\Parameters;
use TwentytwoLabs\Api\Definition\RequestDefinition;
use TwentytwoLabs\Api\Definition\RequestDefinitions;
use TwentytwoLabs\Api\Definition\ResponseDefinition;
use TwentytwoLabs\Api\JsonSchema\Uri\YamlUriRetriever;
use TwentytwoLabs\Api\Schema;

/**
 * Class SwaggerSchemaFactory.
 */
class SwaggerSchemaFactory implements SchemaFactoryInterface
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

    private function resolveSchemaFile(string $schemaFile): \stdClass
    {
        $extension = pathinfo($schemaFile, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'yml':
            case 'yaml':
                if (!class_exists(Yaml::class)) {
                    // @codeCoverageIgnoreStart
                    throw new \InvalidArgumentException('You need to require the "symfony/yaml" component in order to parse yml files');
                    // @codeCoverageIgnoreEnd
                }

                $uriRetriever = new YamlUriRetriever();
                break;
            case 'json':
                $uriRetriever = new UriRetriever();
                break;
            default:
                throw new \InvalidArgumentException(sprintf('file "%s" does not provide a supported extension choose either json, yml or yaml', $schemaFile));
        }

        $schemaStorage = new SchemaStorage($uriRetriever, new UriResolver());

        $schema = $schemaStorage->getSchema($schemaFile);

        // JsonSchema normally defers resolution of $ref values until validation.
        // That does not work for us, because we need to have the complete schema
        // to build definitions.
        $this->expandSchemaReferences($schema, $schemaStorage);

        return $schema;
    }

    /**
     * @param mixed $schema
     */
    private function expandSchemaReferences(&$schema, SchemaStorage $schemaStorage): void
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

    private function createRequestDefinitions(\stdClass $schema): RequestDefinitions
    {
        $definitions = [];
        $defaultConsumedContentTypes = [];
        $defaultProducedContentTypes = [];

        if (isset($schema->consumes)) {
            $defaultConsumedContentTypes = $schema->consumes;
        }
        if (isset($schema->produces)) {
            $defaultProducedContentTypes = $schema->produces;
        }

        $basePath = $schema->basePath ?? '';
        foreach ($schema->paths as $pathTemplate => $methods) {
            foreach ($methods as $method => $definition) {
                $method = strtoupper($method);
                $contentTypes = $defaultConsumedContentTypes;
                if (isset($definition->consumes)) {
                    $contentTypes = $definition->consumes;
                }
                $accepts = $defaultProducedContentTypes;
                if (isset($definition->produces)) {
                    $accepts = $definition->produces;
                }

                if (!isset($definition->operationId)) {
                    throw new \LogicException(sprintf('You need to provide an operationId for %s %s', $method, $pathTemplate));
                }

                if (empty($contentTypes) && $this->containsBodyParametersLocations($definition)) {
                    $contentTypes = $this->guessSupportedContentTypes($definition, $pathTemplate);
                }

                if (!isset($definition->responses)) {
                    throw new \LogicException(sprintf('You need to specify at least one response for %s %s', $method, $pathTemplate));
                }

                if (!isset($definition->parameters)) {
                    $definition->parameters = [];
                }

                $requestParameters = [];
                foreach ($definition->parameters as $parameter) {
                    $requestParameters[] = $this->createParameter($parameter);
                }

                $responseContentTypes = $defaultProducedContentTypes;
                if (isset($definition->produces)) {
                    $responseContentTypes = $definition->produces;
                }

                $responseDefinitions = [];
                foreach ($definition->responses as $statusCode => $response) {
                    $responseDefinitions[] = $this->createResponseDefinition(
                        'default' === $statusCode ? $statusCode : (int) $statusCode,
                        $responseContentTypes,
                        $response
                    );
                }

                $definitions[] = new RequestDefinition(
                    $method,
                    $definition->operationId,
                    '/' === $basePath ? $pathTemplate : $basePath.$pathTemplate,
                    new Parameters($requestParameters),
                    $contentTypes,
                    $accepts,
                    $responseDefinitions
                );
            }
        }

        return new RequestDefinitions($definitions);
    }

    private function containsBodyParametersLocations(\stdClass $definition): bool
    {
        foreach ($definition->parameters ?? [] as $parameter) {
            if (\in_array($parameter->in, Parameter::BODY_LOCATIONS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function guessSupportedContentTypes(\stdClass $definition, string $pathTemplate): array
    {
        $bodyLocations = [];
        foreach ($definition->parameters as $parameter) {
            if (isset($parameter->in) && \in_array($parameter->in, Parameter::BODY_LOCATIONS, true)) {
                $bodyLocations[] = $parameter->in;
            }
        }

        if (count($bodyLocations) > 1) {
            throw new \LogicException(sprintf('Parameters cannot have %s locations at the same time in %s', implode(' and ', $bodyLocations), $pathTemplate));
        }

        return [Parameter::BODY_LOCATIONS_TYPES[current($bodyLocations)]];
    }

    /**
     * @param int|string $statusCode
     * @param string[]   $allowedContentTypes
     */
    private function createResponseDefinition($statusCode, array $allowedContentTypes, \stdClass $response): ResponseDefinition
    {
        $parameters = [];
        if (isset($response->schema)) {
            $parameters[] = $this->createParameter((object) [
                'in' => 'body',
                'name' => 'body',
                'required' => true,
                'schema' => $response->schema,
            ]);
        }

        if (isset($response->headers)) {
            foreach ($response->headers as $headerName => $schema) {
                $schema->in = 'header';
                $schema->name = $headerName;
                $schema->required = true;
                $parameters[] = $this->createParameter($schema);
            }
        }

        return new ResponseDefinition($statusCode, $allowedContentTypes, new Parameters($parameters));
    }

    private function createParameter(\stdClass $parameter): Parameter
    {
        $parameter = get_object_vars($parameter);
        $location = $parameter['in'];
        $name = $parameter['name'];
        $schema = $parameter['schema'] ?? new \stdClass();
        $required = $parameter['required'] ?? false;

        unset($parameter['in'], $parameter['name'], $parameter['required'], $parameter['schema']);

        // Every remaining parameter may be json schema properties
        foreach ($parameter as $key => $value) {
            $schema->{$key} = $value;
        }

        // It's not relevant to validate file type
        if (isset($schema->format) && 'file' === $schema->format) {
            $schema = null;
        }

        return new Parameter($location, $name, $required, $schema);
    }
}
