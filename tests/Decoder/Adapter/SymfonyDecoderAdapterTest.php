<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiValidator\Tests\Decoder\Adapter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\DecoderInterface as SymfonyDecoderInterface;
use TwentytwoLabs\ApiValidator\Decoder\Adapter\SymfonyDecoderAdapter;

final class SymfonyDecoderAdapterTest extends TestCase
{
    private SymfonyDecoderInterface|MockObject $decoder;

    protected function setUp(): void
    {
        $this->decoder = $this->createMock(SymfonyDecoderInterface::class);
    }

    /**
     * @param array<string, mixed> $context
     */
    #[DataProvider('getData')]
    public function testShouldDecodeJson(string $data, string $format, array $context): void
    {
        $this->decoder
            ->expects($this->once())
            ->method('decode')
            ->with($data, $format, $context)
            ->willReturn([['foo' => 'foo1'], ['foo' => 'foo2']])
        ;

        $decoder = $this->getAdapter();
        $this->assertSame([['foo' => 'foo1'], ['foo' => 'foo2']], $decoder->decode($data, $format));
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function getData(): array
    {
        return [
            [
                '<response><item key="0"><foo>foo1</foo></item><item key="1"><foo>foo2</foo></item></response>',
                'xml',
                [],
            ],
            [
                '<response><item key="0"><foo>foo1</foo></item><item key="1"><foo>foo2</foo></item></response>',
                'application/xml',
                [],
            ],
            [
                '<response><item key="0"><foo>foo1</foo></item><item key="1"><foo>foo2</foo></item></response>',
                'application/xml; charset=utf8',
                [],
            ],

            [
                '[{"foo": "foo1"}, {"foo": "foo2"}]',
                'json',
                ['json_decode_associative' => false],
            ],
            [
                '[{"foo": "foo1"}, {"foo": "foo2"}]',
                'application/json',
                ['json_decode_associative' => false],
            ],
            [
                '[{"foo": "foo1"}, {"foo": "foo2"}]',
                'application/json; charset=utf8',
                ['json_decode_associative' => false],
            ],
        ];
    }

    private function getAdapter(): SymfonyDecoderAdapter
    {
        return new SymfonyDecoderAdapter($this->decoder);
    }
}
