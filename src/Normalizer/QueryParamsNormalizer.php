<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Normalizer;

/**
 * Class QueryParamsNormalizer.
 */
class QueryParamsNormalizer
{
    /**
     * @param array     $queryParams       An array of query parameters
     * @param \stdClass $queryParamsSchema A JSON Schema of query params
     *
     * @return array An array of query parameters with the proper types
     */
    public static function normalize(array $queryParams, \stdClass $queryParamsSchema): array
    {
        foreach ($queryParamsSchema->properties as $name => $queryParamSchema) {
            $type = $queryParamSchema->type ?? 'string';

            if (array_key_exists($name, $queryParams)) {
                switch ($type) {
                    case 'boolean':
                        if ('false' === $queryParams[$name]) {
                            $queryParams[$name] = false;
                        }
                        if ('true' === $queryParams[$name]) {
                            $queryParams[$name] = true;
                        }
                        if (in_array($queryParams[$name], ['0', '1'])) {
                            $queryParams[$name] = (bool) $queryParams[$name];
                        }
                        break;
                    case 'integer':
                        if (is_numeric($queryParams[$name])) {
                            $queryParams[$name] = (int) $queryParams[$name];
                        }
                        break;
                    case 'number':
                        if (is_numeric($queryParams[$name])) {
                            $queryParams[$name] = (float) $queryParams[$name];
                        }
                        break;
                }

                if (isset($queryParamSchema->collectionFormat)) {
                    switch ($queryParamSchema->collectionFormat) {
                        case 'csv':
                            $separator = ',';
                            break;
                        case 'ssv':
                            $separator = ' ';
                            break;
                        case 'pipes':
                            $separator = '|';
                            break;
                        case 'tsv':
                            $separator = "\t";
                            break;
                        default:
                            throw new \InvalidArgumentException(sprintf('%s is not a supported query collection format', $queryParamSchema->collectionFormat));
                    }

                    $queryParams[$name] = explode($separator, $queryParams[$name]);
                }
            }
        }

        return $queryParams;
    }
}
