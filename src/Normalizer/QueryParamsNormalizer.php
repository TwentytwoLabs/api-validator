<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Normalizer;

/**
 * @internal
 */
final class QueryParamsNormalizer
{
    /**
     * @param array $queryParams       An array of query parameters
     * @param array $queryParamsSchema A JSON Schema of query params
     *
     * @return array An array of query parameters with the proper types
     */
    public static function normalize(array $queryParams, array $queryParamsSchema): array
    {
        foreach ($queryParamsSchema['properties'] as $name => $queryParamSchema) {
            if (array_key_exists($name, $queryParams)) {
                $queryParams[$name] = match ($queryParamSchema['type'] ?? 'string') {
                    'boolean' => filter_var($queryParams[$name], FILTER_VALIDATE_BOOLEAN),
                    'integer' => is_numeric($queryParams[$name]) ? (int) $queryParams[$name] : $queryParams[$name],
                    'number' => is_numeric($queryParams[$name]) ? (float) $queryParams[$name] : $queryParams[$name],
                    default => $queryParams[$name],
                };

                if (!empty($queryParamSchema['collectionFormat'])) {
                    $separator = match ($queryParamSchema['collectionFormat']) {
                        'csv' => ',',
                        'ssv' => ' ',
                        'pipes' => '|',
                        'tsv' => "\t",
                        default => throw new \InvalidArgumentException(sprintf('%s is not a supported query collection format', $queryParamSchema['collectionFormat']))
                    };

                    $queryParams[$name] = explode($separator, $queryParams[$name]);
                }
            }
        }

        return $queryParams;
    }
}
