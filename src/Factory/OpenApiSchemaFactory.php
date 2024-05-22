<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Factory;

use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinitions;
use TwentytwoLabs\ApiValidator\Definition\Parameter;
use TwentytwoLabs\ApiValidator\Definition\Parameters;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;

final class OpenApiSchemaFactory extends AbstractSchemaFactory
{
    protected function createOperationDefinitions(array $schema): OperationDefinitions
    {
        $securityDefinitions = $this->createSecurityDefinitions($schema);
        $definitions = [];
        foreach ($schema['paths'] ?? [] as $pathTemplate => $methods) {
            foreach ($methods as $method => $definition) {
                if ('parameters' === $method) {
                    continue;
                }

                $method = strtoupper($method);

                if (empty($definition['responses'])) {
                    $message = sprintf('You need to specify at least one response for %s %s', $method, $pathTemplate);
                    throw new \LogicException($message);
                }

                $requestParameters = $this->createRequestParameters($definition, $securityDefinitions);
                $responses = $this->createResponseDefinitions($definition['responses']);

                $accepts = [];
                /** @var ResponseDefinition $response */
                foreach ($responses as $response) {
                    $accepts = array_merge($accepts, array_keys($response->getBodySchema()));
                }

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

                $definitions[] = new OperationDefinition(
                    $method,
                    $definition['operationId'] ?? hash('md5', uniqid('', true)),
                    $pathTemplate,
                    $requestParameters,
                    $responses
                );
            }
        }

        return new OperationDefinitions($definitions);
    }

    private function createSecurityDefinitions(array $schema): array
    {
        $securities = [];
        foreach ($schema['security'] as $items) {
            foreach ($items as $key => $item) {
                $securityScheme = $schema['components']['securitySchemes'][$key] ?? null;
                if (null === $securityScheme) {
                    throw new \LogicException(sprintf('You must define a security scheme with name %s', $key));
                }

                $securityScheme['in'] = $securityScheme['in'] ?? 'header';
                $securityScheme['type'] = 'string';
                $securityScheme['name'] = 'header' !== $securityScheme['in'] ? strtolower($key) : 'authorization';

                $securities[] = $this->createParameter($securityScheme);
            }
        }

        return $securities;
    }

    private function createRequestParameters(array $definition, array $securityDefinitions): Parameters
    {
        $requestParameters = array_key_exists('security', $definition) ? [] : $securityDefinitions;
        foreach ($definition['parameters'] ?? [] as $parameter) {
            $requestParameters[] = $this->createParameter($parameter);
        }

        $contentTypes = array_keys($definition['requestBody']['content'] ?? []);
        $requestParameters[] = $this->createBodyParameter($definition['requestBody']['content'] ?? []);

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

    private function createBodyParameter(array $contents): Parameter
    {
        $requestParameters = ['name' => 'body', 'in' => 'body', 'required' => true, 'schema' => []];
        foreach ($contents as $contentType => $content) {
            $requestParameters['schema'][$contentType] = $content['schema'];
        }

        return $this->createParameter($requestParameters);
    }

    private function createResponseDefinitions(array $responses): array
    {
        $responseDefinitions = [];
        foreach ($responses as $statusCode => $response) {
            $responseDefinitions[] = $this->createResponseDefinition($statusCode, $response);
        }

        return $responseDefinitions;
    }

    private function createResponseDefinition(int|string $statusCode, array $response): ResponseDefinition
    {
        $parameters = [];
        foreach ($response['headers'] ?? [] as $headerName => $schema) {
            $schema['in'] = 'header';
            $schema['name'] = $headerName;
            $schema['required'] = true;
            $parameters[] = $this->createParameter($schema);
        }

        if (!empty($response['content'])) {
            $schema = [];
            foreach ($response['content'] as $contentType => $content) {
                if (!empty($content['schema'])) {
                    $schema[$contentType] = $content;
                }
            }

            $parameters[] = $this->createParameter([
                'in' => 'body',
                'name' => 'body',
                'required' => true,
                'schema' => $schema,
            ]);
        }

        return new ResponseDefinition($statusCode, new Parameters($parameters));
    }
}
