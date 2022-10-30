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

class SwaggerSchemaFactory extends AbstractSchemaFactory
{
    protected function createRequestDefinitions(\stdClass $schema): RequestDefinitions
    {
        $definitions = [];

        $defaultConsumedContentTypes = array_unique($schema->consumes ?? []);
        $defaultProducedContentTypes = array_unique($schema->produces ?? []);

        $basePath = $schema->basePath ?? '';
        foreach ($schema->paths as $pathTemplate => $methods) {
            foreach ($methods as $method => $definition) {
                $method = strtoupper($method);

                if (!isset($definition->operationId)) {
                    throw new \LogicException(sprintf('You need to provide an operationId for %s %s', $method, $pathTemplate));
                }

                if (!isset($definition->responses)) {
                    throw new \LogicException(sprintf('You need to specify at least one response for %s %s', $method, $pathTemplate));
                }

                $contentTypes = array_unique(array_merge($defaultConsumedContentTypes, $definition->consumes ?? []));
                if (empty($contentTypes) && $this->containsBodyParametersLocations($definition)) {
                    $contentTypes = $this->guessSupportedContentTypes($definition, $pathTemplate);
                }

                $requestParameters = [];
                foreach ($definition->parameters ?? [] as $parameter) {
                    $requestParameters[] = $this->createParameter(get_object_vars($parameter));
                }

                $responseContentTypes = array_unique(array_merge($defaultProducedContentTypes, $definition->produces ?? []));
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
                    array_unique(array_merge($defaultProducedContentTypes, $definition->produces ?? [])),
                    $responseDefinitions
                );
            }
        }

        return new RequestDefinitions($definitions);
    }

    protected function createResponseDefinition(int|string $statusCode, array $allowedContentTypes, \stdClass $response): ResponseDefinition
    {
        $parameters = [];
        if (isset($response->schema)) {
            $parameters[] = $this->createParameter([
                'in' => 'body',
                'name' => 'body',
                'required' => true,
                'schema' => $response->schema,
            ]);
        }

        if (isset($response->headers)) {
            foreach ($response->headers as $headerName => $schema) {
                $schema->name = $headerName;
                $schema->in = 'header';
                $schema->required = true;
                $parameters[] = $this->createParameter(get_object_vars($schema));
            }
        }

        return new ResponseDefinition($statusCode, $allowedContentTypes, new Parameters($parameters));
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

    /** @return string[] */
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

    private function createParameter(array $parameter): Parameter
    {
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
