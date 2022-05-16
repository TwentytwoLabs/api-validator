<?php

declare(strict_types=1);

namespace TwentytwoLabs\Api\Encoder;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Class ProblemJsonEncoder.
 */
class ProblemJsonEncoder extends JsonEncoder
{
    public function supportsDecoding($format): bool
    {
        return 'problem+json' === $format;
    }
}
