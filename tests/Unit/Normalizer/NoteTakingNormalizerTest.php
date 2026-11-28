<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Normalizer;

use ArrayObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\NoteTaking;
use SpomkyLabs\PwaBundle\Dto\Url;
use SpomkyLabs\PwaBundle\Normalizer\NoteTakingNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class NoteTakingNormalizerTest extends TestCase
{
    #[Test]
    public function theUrlIsWrittenAsNewNoteUrl(): void
    {
        // Given
        $url = new Url('/notes/new');
        $noteTaking = new NoteTaking();
        $noteTaking->url = $url;
        $inner = $this->createMock(NormalizerInterface::class);
        $inner->expects(static::once())
            ->method('normalize')
            ->with($url)
            ->willReturn('/notes/new');
        $normalizer = new NoteTakingNormalizer();
        $normalizer->setNormalizer($inner);

        // When
        $normalized = $normalizer->normalize($noteTaking, 'json');

        // Then
        static::assertSame([
            'new_note_url' => '/notes/new',
        ], $normalized);
    }

    #[Test]
    public function anEmptyNoteTakingFollowsTheEmptyObjectsContext(): void
    {
        // Given
        $normalizer = new NoteTakingNormalizer();
        $normalizer->setNormalizer(static::createStub(NormalizerInterface::class));

        // When
        $withoutContext = $normalizer->normalize(new NoteTaking(), 'json');
        $preserved = $normalizer->normalize(new NoteTaking(), 'json', [
            AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
        ]);

        // Then
        static::assertSame([], $withoutContext);
        static::assertInstanceOf(ArrayObject::class, $preserved);
        static::assertCount(0, $preserved);
    }
}
