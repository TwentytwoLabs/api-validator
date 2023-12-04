<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Validator;

use JsonSchema\Validator;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rize\UriTemplate;
use TwentytwoLabs\ApiValidator\Decoder\DecoderInterface;
use TwentytwoLabs\ApiValidator\Decoder\DecoderUtils;
use TwentytwoLabs\ApiValidator\Definition\MessageDefinition;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;
use TwentytwoLabs\ApiValidator\Normalizer\QueryParamsNormalizer;

final class MessageValidator
{
    private Validator $validator;
    private array $violations = [];
    private DecoderInterface $decoder;

    public function __construct(Validator $validator, DecoderInterface $decoder)
    {
        $this->validator = $validator;
        $this->decoder = $decoder;
    }

    public function validateRequest(RequestInterface $request, OperationDefinition $definition): void
    {
        $this->validateHeaders($request, $definition);
        $this->validatePath($request, $definition);
        $this->validateQueryParameters($request, $definition);

        if ($definition->hasBodySchema()) {
            $contentTypeValid = $this->validateContentType($request, $definition);
            if ($contentTypeValid && in_array($request->getMethod(), ['PUT', 'PATCH', 'POST'])) {
                $this->validateMessageBody($request, $definition);
            }
        }
    }

    public function validateResponse(ResponseInterface $response, OperationDefinition $definition): void
    {
        $responseDefinition = $definition->getResponseDefinition($response->getStatusCode());
        $this->validateHeaders($response, $responseDefinition);
        $contentTypeValid = $this->validateContentType($response, $responseDefinition);
        if ($contentTypeValid) {
            $this->validateMessageBody($response, $responseDefinition);
        }
    }

    public function hasViolations(): bool
    {
        return !empty($this->violations);
    }

    /**
     * @return ConstraintViolation[]
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    private function validateHeaders(MessageInterface $message, MessageDefinition $definition): void
    {
        if ($definition->hasHeadersSchema()) {
            // Transform each header values into a string
            $headers = array_map(
                function (array $values) {
                    return implode(', ', $values);
                },
                $message->getHeaders()
            );

            $this->validate((object) array_change_key_case($headers), $definition->getHeadersSchema(), 'header');
        }
    }

    private function validateContentType(MessageInterface $message, MessageDefinition $definition): bool
    {
        $contentType = $message->getHeaderLine('Content-Type');
        if (!empty($contentType)) {
            $contentType = explode(';', $message->getHeaderLine('Content-Type'));
            $contentType = $contentType[0];
        }

        if (empty($contentType) && $definition instanceof ResponseDefinition && 204 === $definition->getStatusCode()) {
            return true;
        }

        $contentTypes = $definition->getContentTypes();
        if (!in_array($contentType, $contentTypes, true)) {
            if ('' === $contentType) {
                $violationMessage = 'Content-Type should not be empty';
                $constraint = 'required';
            } else {
                $violationMessage = sprintf(
                    '%s is not a supported content type, supported: %s',
                    $message->getHeaderLine('Content-Type'),
                    implode(', ', $contentTypes)
                );
                $constraint = 'enum';
            }

            $this->addViolation(new ConstraintViolation('Content-Type', $violationMessage, $constraint, 'header'));

            return false;
        }

        return true;
    }

    private function validatePath(RequestInterface $request, OperationDefinition $definition): void
    {
        if ($definition->hasPathSchema()) {
            $template = new UriTemplate();
            $params = $template->extract($definition->getPathTemplate(), $request->getUri()->getPath());
            $schema = $definition->getPathSchema();

            $this->validate((object) $params, $schema, 'path');
        }
    }

    private function validateQueryParameters(RequestInterface $request, OperationDefinition $definition): void
    {
        if ($definition->hasQueryParametersSchema()) {
            $queryParams = [];
            $query = $request->getUri()->getQuery();
            if (!empty($query)) {
                foreach (explode('&', $query) as $item) {
                    $tmp = explode('=', $item);
                    $queryParams[$tmp[0]] = $tmp[1];
                }
            }
            $schema = $definition->getQueryParametersSchema();
            $queryParams = QueryParamsNormalizer::normalize($queryParams, $schema);

            $this->validate((object) $queryParams, $schema, 'query');
        }
    }

    private function validateMessageBody(MessageInterface $message, MessageDefinition $definition): void
    {
        if (!$definition->hasBodySchema()) {
            return;
        }

        if ($message instanceof ServerRequestInterface) {
            $body = $message->getParsedBody();
            $bodyString = !empty($body) ? json_encode($body) : '';
        } else {
            $bodyString = (string) $message->getBody();
        }

        $contentType = $message->getHeaderLine('Content-Type');
        $decodedBody = $this->decoder->decode($bodyString, DecoderUtils::extractFormatFromContentType($contentType));

        $this->validate($decodedBody, $definition->getBodySchema(), 'body');
    }

    private function validate(mixed $data, array $schema, string $location): void
    {
        $this->validator->check($data, json_decode(json_encode($schema)));
        if (!$this->validator->isValid()) {
            $violations = array_map(
                function (array $error) use ($location) {
                    return new ConstraintViolation(
                        $error['property'],
                        $error['message'],
                        $error['constraint'],
                        $location
                    );
                },
                $this->validator->getErrors()
            );

            foreach ($violations as $violation) {
                $this->addViolation($violation);
            }
        }

        $this->validator->reset();
    }

    private function addViolation(ConstraintViolation $violation)
    {
        $this->violations[] = $violation;
    }
}
