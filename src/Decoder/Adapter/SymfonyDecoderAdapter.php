<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Decoder\Adapter;

use Symfony\Component\Serializer\Encoder\DecoderInterface as SymfonyDecoderInterface;
use TwentytwoLabs\Api\Decoder\DecoderInterface;

/**
 * Class SymfonyDecoderAdapter.
 */
class SymfonyDecoderAdapter implements DecoderInterface
{
    private SymfonyDecoderInterface $decoder;

    public function __construct(SymfonyDecoderInterface $decoder)
    {
        $this->decoder = $decoder;
    }

    public function decode(string $data, string $format)
    {
        $context = [];

        if (preg_match('#json#', $format)) {
            // the JSON schema validator need an object hierarchy
            $context['json_decode_associative'] = false;
        }

        $decoded = $this->decoder->decode($data, $format, $context);

        if ('xml' === $format) {
            // the JSON schema validator need an object hierarchy
            $decoded = json_decode(json_encode($decoded));
        }

        return $decoded;
    }
}
