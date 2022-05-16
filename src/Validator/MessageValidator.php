<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Validator;

use JsonSchema\Validator;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rize\UriTemplate;
use TwentytwoLabs\Api\Decoder\DecoderInterface;
use TwentytwoLabs\Api\Decoder\DecoderUtils;
use TwentytwoLabs\Api\Definition\MessageDefinition;
use TwentytwoLabs\Api\Definition\RequestDefinition;
use TwentytwoLabs\Api\Normalizer\QueryParamsNormalizer;

/**
 * Class MessageValidator.
 */
class MessageValidator
{
    private Validator $validator;
    private array $violations = [];
    private DecoderInterface $decoder;

    public function __construct(Validator $validator, DecoderInterface $decoder)
    {
        $this->validator = $validator;
        $this->decoder = $decoder;
    }

    public function validateRequest(RequestInterface $request, RequestDefinition $definition): void
    {
        if ($definition->hasBodySchema()) {
            $contentTypeValid = $this->validateContentType($request, $definition);
            if ($contentTypeValid && in_array($request->getMethod(), ['PUT', 'PATCH', 'POST'])) {
                $this->validateMessageBody($request, $definition);
            }
        }

        $this->validateHeaders($request, $definition);
        $this->validatePath($request, $definition);
        $this->validateQueryParameters($request, $definition);
    }

    public function validateResponse(ResponseInterface $response, RequestDefinition $definition): void
    {
        $responseDefinition = $definition->getResponseDefinition($response->getStatusCode());
        if ($responseDefinition->hasBodySchema()) {
            $contentTypeValid = $this->validateContentType($response, $responseDefinition);
            if ($contentTypeValid) {
                $this->validateMessageBody($response, $responseDefinition);
            }
        }

        $this->validateHeaders($response, $responseDefinition);
    }

    public function validateHeaders(MessageInterface $message, MessageDefinition $definition): void
    {
        if ($definition->hasHeadersSchema()) {
            // Transform each header values into a string
            $headers = array_map(
                function (array $values) {
                    return implode(', ', $values);
                },
                $message->getHeaders()
            );

            $this->validate(
                (object) array_change_key_case($headers, CASE_LOWER),
                $definition->getHeadersSchema(),
                'header'
            );
        }
    }

    public function validateMessageBody(MessageInterface $message, MessageDefinition $definition): void
    {
        if ($message instanceof ServerRequestInterface) {
            $body = $message->getParsedBody();
            $bodyString = !empty($body) ? json_encode($body) : '';
        } else {
            $bodyString = (string) $message->getBody();
        }

        if (!empty($bodyString) && $definition->hasBodySchema()) {
            $contentType = $message->getHeaderLine('Content-Type');
            $decodedBody = $this->decoder->decode(
                $bodyString,
                DecoderUtils::extractFormatFromContentType($contentType)
            );

            $this->validate($decodedBody, $definition->getBodySchema(), 'body');
        }
    }

    public function validateContentType(MessageInterface $message, MessageDefinition $definition): bool
    {
        $contentType = explode(';', $message->getHeaderLine('Content-Type'));
        $contentTypes = $definition->getContentTypes();

        if (!in_array($contentType[0], $contentTypes, true)) {
            if ('' === $contentType[0]) {
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

            $this->addViolation(
                new ConstraintViolation(
                    'Content-Type',
                    $violationMessage,
                    $constraint,
                    'header'
                )
            );

            return false;
        }

        return true;
    }

    public function validatePath(RequestInterface $request, RequestDefinition $definition): void
    {
        if ($definition->hasPathSchema()) {
            $template = new UriTemplate();
            $params = $template->extract($definition->getPathTemplate(), $request->getUri()->getPath());
            $schema = $definition->getPathSchema();

            $this->validate(
                (object) $params,
                $schema,
                'path'
            );
        }
    }

    public function validateQueryParameters(RequestInterface $request, RequestDefinition $definition): void
    {
        if ($definition->hasQueryParametersSchema()) {
            $queryParams = [];
            $query = $request->getUri()->getQuery();
            if ('' !== $query) {
                foreach (explode('&', $query) as $item) {
                    $tmp = explode('=', $item);
                    $queryParams[$tmp[0]] = $tmp[1];
                }
            }
            $schema = $definition->getQueryParametersSchema();
            $queryParams = QueryParamsNormalizer::normalize($queryParams, $schema);

            $this->validate(
                (object) $queryParams,
                $schema,
                'query'
            );
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

    /**
     * @param mixed $data
     */
    private function validate($data, \stdClass $schema, string $location): void
    {
        $this->validator->coerce($data, $schema);
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
