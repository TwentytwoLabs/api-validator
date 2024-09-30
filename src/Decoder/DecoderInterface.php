<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Decoder;

interface DecoderInterface
{
    /**
     * @return array<int|string, mixed>
     */
    public function decode(string $data, string $format): \stdClass|array;
}
