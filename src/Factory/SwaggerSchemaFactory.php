<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Factory;

use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinitions;
use TwentytwoLabs\ApiValidator\Definition\Parameters;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;

final class SwaggerSchemaFactory extends AbstractSchemaFactory
{
    protected function createOperationDefinitions(array $schema): OperationDefinitions
    {
        $definitions = [];

        $defaultConsumedContentTypes = $schema['consumes'] ?? [];
        $defaultProducedContentTypes = $schema['produces'] ?? [];

        $securityDefinitions = $this->createSecurityDefinitions($schema);

        foreach ($schema['paths'] ?? [] as $pathTemplate => $methods) {
            foreach ($methods as $method => $definition) {
                $method = strtoupper($method);

                if (!isset($definition['responses'])) {
                    throw new \LogicException(sprintf('You need to specify at least one response for %s %s', $method, $pathTemplate));
                }

                $requestParameters = $this->createRequestParameters($defaultConsumedContentTypes, $pathTemplate, $definition, $securityDefinitions);

                $accepts = array_merge($defaultProducedContentTypes, $definition['produces'] ?? []);
                $acceptsParameter = $this->createParameter([
                    'name' => 'accept',
                    'in' => 'header',
                    'required' => true,
                    'schema' => [
                        'type' => 'string',
                        'default' => 'application/json',
                        'enum' => array_values(array_unique($accepts)),
                    ],
                ]);

                $requestParameters->addParameter($acceptsParameter);

                $responseDefinitions = [];
                foreach ($definition['responses'] as $statusCode => $response) {
                    $responseDefinitions[] = $this->createResponseDefinition($statusCode, $response);
                }

                $definitions[] = new OperationDefinition(
                    $method,
                    $definition['operationId'] ?? hash('md5', uniqid('', true)),
                    $pathTemplate,
                    $requestParameters,
                    $responseDefinitions
                );
            }
        }

        return new OperationDefinitions($definitions);
    }

    private function createResponseDefinition(int|string $statusCode, array $response): ResponseDefinition
    {
        $parameters = [];
        if (isset($response['schema'])) {
            $parameters[] = $this->createParameter([
                'in' => 'body',
                'name' => 'body',
                'required' => true,
                'schema' => $response['schema'],
            ]);
        }

        foreach ($response['headers'] ?? [] as $name => $schema) {
            $parameters[] = $this->createParameter(array_merge($schema, ['name' => $name, 'in' => 'header']));
        }

        return new ResponseDefinition($statusCode, new Parameters($parameters));
    }

    private function containsBodyParametersLocations(array $definition): bool
    {
        foreach ($definition['parameters'] ?? [] as $parameter) {
            if (\in_array($parameter['in'], ['formData', 'body'], true)) {
                return true;
            }
        }

        return false;
    }

    private function guessSupportedContentTypes(array $definition, string $pathTemplate): array
    {
        $bodyLocationsTypes = ['formData' => 'application/x-www-form-urlencoded', 'body' => 'application/json'];

        $bodyLocations = [];
        foreach ($definition['parameters'] ?? [] as $parameter) {
            if (\in_array($parameter['in'] ?? '', ['formData', 'body'], true)) {
                $bodyLocations[] = $parameter['in'];
            }
        }

        if (count($bodyLocations) > 1) {
            throw new \LogicException(sprintf('Parameters cannot have %s locations at the same time in %s', implode(' and ', $bodyLocations), $pathTemplate));
        }

        return [$bodyLocationsTypes[current($bodyLocations)]];
    }

    private function createSecurityDefinitions(array $schema): array
    {
        $securities = [];
        foreach ($schema['security'] ?? [] as $items) {
            foreach ($items as $key => $item) {
                $securityScheme = $schema['securityDefinitions'][$key] ?? null;
                if (null === $securityScheme) {
                    throw new \LogicException(sprintf('You must define a security scheme with name %s', $key));
                }

                $securityScheme['type'] = 'string';
                $securityScheme['required'] = true;
                $securityScheme['name'] = strtolower($securityScheme['name']);

                $securities[] = $this->createParameter($securityScheme);
            }
        }

        return $securities;
    }

    private function createRequestParameters(
        array $defaultConsumedContentTypes,
        string $pathTemplate,
        array $definition,
        array $securityDefinitions
    ): Parameters {
        $requestParameters = array_key_exists('security', $definition) ? [] : $securityDefinitions;
        foreach ($definition['parameters'] ?? [] as $parameter) {
            $requestParameters[] = $this->createParameter($parameter);
        }

        $contentTypes = array_unique(array_merge($defaultConsumedContentTypes, $definition['consumes'] ?? []));
        if (empty($contentTypes) && $this->containsBodyParametersLocations($definition)) {
            $contentTypes = $this->guessSupportedContentTypes($definition, $pathTemplate);
        }

        if (!empty($contentTypes)) {
            $requestParameters[] = $this->createParameter([
                'name' => 'content-type',
                'in' => 'header',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                    'default' => 'application/json',
                    'enum' => $contentTypes,
                ],
            ]);
        }

        return new Parameters($requestParameters);
    }
}
