<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Definition;

interface MessageDefinition
{
    /**
     * Check if a schema for headers is available.
     */
    public function hasHeadersSchema(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getHeadersSchema(): array;

    /**
     * @return array<int, string>
     */
    public function getContentTypes(): array;

    /**
     * Check if a schema for body is available.
     */
    public function hasBodySchema(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getBodySchema(): array;
}
