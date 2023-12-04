<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Decoder\Adapter;

use Symfony\Component\Serializer\Encoder\DecoderInterface as SymfonyDecoderInterface;
use TwentytwoLabs\ApiValidator\Decoder\DecoderInterface;

final class SymfonyDecoderAdapter implements DecoderInterface
{
    private SymfonyDecoderInterface $decoder;

    public function __construct(SymfonyDecoderInterface $decoder)
    {
        $this->decoder = $decoder;
    }

    public function decode(string $data, string $format)
    {
        $context = [];

        if (str_contains($format, 'json')) {
            // the JSON schema validator need an object hierarchy
            $context['json_decode_associative'] = false;
        }

        return $this->decoder->decode($data, $format, $context);
    }
}
