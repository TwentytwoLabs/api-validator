<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Decoder;

interface DecoderInterface
{
    public function decode(string $data, string $format);
}
