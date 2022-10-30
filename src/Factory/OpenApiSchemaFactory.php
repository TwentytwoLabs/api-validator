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

class OpenApiSchemaFactory extends AbstractSchemaFactory
{
    protected function createRequestDefinitions(\stdClass $schema): RequestDefinitions
    {
        $definitions = [];
        $defaultConsumedContentTypes = [];
        $defaultProducedContentTypes = [];

        $basePath = $schema->basePath ?? '';
        foreach ($schema->paths as $pathTemplate => $methods) {
            foreach ($methods as $method => $definition) {
                if ('parameters' === $method) {
                    continue;
                }

                $method = strtoupper($method);
                if (!isset($definition->operationId)) {
                    throw new \LogicException(sprintf('You need to provide an operationId for %s %s', $method, $pathTemplate));
                }

                if (!isset($definition->responses)) {
                    throw new \LogicException(
                        sprintf('You need to specify at least one response for %s %s', $method, $pathTemplate)
                    );
                }

                $contentTypes = array_merge(
                    $defaultConsumedContentTypes,
                    array_keys(get_object_vars($definition->requestBody->content ?? new \stdClass()))
                );

                $accepts = $defaultProducedContentTypes;
                foreach ($definition->responses as $response) {
                    if (!empty($response->content)) {
                        $accepts = array_unique(array_merge($accepts, array_keys(get_object_vars($response->content))));
                    }
                }

                $requestParameters = [];
                foreach ($definition->parameters ?? [] as $parameter) {
                    $requestParameters[] = $this->createParameter(get_object_vars($parameter));
                }

                if (!empty($contentTypes) && !empty($definition->requestBody->content->{$contentTypes[0]})) {
                    $s = $definition->requestBody->content->{$contentTypes[0]};
                    $s->name = 'body';
                    $s->in = 'body';
                    $s->required = true;
                    $requestParameters[] = $this->createParameter(get_object_vars($s));
                }

                $responseDefinitions = [];
                foreach ($definition->responses as $statusCode => $response) {
                    if (empty($response->content)) {
                        continue;
                    }

                    $responseDefinitions[] = $this->createResponseDefinition(
                        'default' === $statusCode ? $statusCode : (int) $statusCode,
                        $accepts,
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

    protected function createResponseDefinition(
        int|string $statusCode,
        array $allowedContentTypes,
        \stdClass $response
    ): ResponseDefinition {
        $parameters = [];
        if (isset($response->headers)) {
            foreach ($response->headers as $headerName => $schema) {
                $schema->in = 'header';
                $schema->name = $headerName;
                $schema->required = true;
                $parameters[] = $this->createParameter(get_object_vars($schema));
            }
        }

        if (!empty($response->content)) {
            $parameters[] = $this->createParameter([
                'in' => 'body',
                'name' => 'body',
                'required' => true,
                'schema' => $response->content->{$allowedContentTypes[0]}->schema,
            ]);
        }

        return new ResponseDefinition($statusCode, $allowedContentTypes, new Parameters($parameters));
    }

    private function createParameter(array $parameter): Parameter
    {
        $name = $parameter['name'];
        $schema = $parameter['schema'] ?? new \stdClass();
        $required = $parameter['required'] ?? false;
        $location = $parameter['in'];

        unset($parameter['in'], $parameter['required'], $parameter['name'], $parameter['schema']);

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
