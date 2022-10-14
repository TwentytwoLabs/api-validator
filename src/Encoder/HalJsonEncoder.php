<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Encoder;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Class HalJsonEncoder.
 */
class HalJsonEncoder extends JsonEncoder
{
    /**
     * {@inheritdoc}
     */
    public function decode(string $data, string $format, array $context = []): mixed
    {
        $valid = isset($context['json_decode_associative']);
        unset($context['json_decode_associative']);
        $data = $this->decodingImpl->decode($data, self::FORMAT, $context);

        if ($valid && isset($data['_embedded']['item'])) {
            $items = array_map(function ($item) {
                $item = array_merge($item, $this->getEmbedded($item['_embedded'] ?? []));
                unset($item['_links'], $item['_embedded']);

                return $item;
            }, $data['_embedded']['item']);

            return $valid ? json_decode(json_encode($items)) : $items;
        }

        if (!isset($data['_embedded']['item'])) {
            $data = array_merge($this->getEmbedded($data['_embedded'] ?? []), $data);
            unset($data['_links'], $data['_embedded']);
        }

        return $valid ? json_decode(json_encode($data)) : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = []): string
    {
        return $this->encodingImpl->encode($data, 'json', $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format): bool
    {
        return 'hal+json' === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format): bool
    {
        return 'hal+json' === $format;
    }

    private function getEmbedded(array $items): array
    {
        return array_map(function ($item) {
            unset($item['_links']);

            return $item;
        }, $items);
    }
}
