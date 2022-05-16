<?php

namespace TwentytwoLabs\Api\Decoder;

/**
 * Interface DecoderInterface.
 */
interface DecoderInterface
{
    public function decode(string $data, string $format);
}
