<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class OtherPagesController extends AbstractController
{
    #[Route('/audio-file-handler/{param1}', name: 'audio_file_handler')]
    public function dummy1(string $param1): void
    {
    }

    #[Route('/shared-content-receiver/{param1}/{param2}', name: 'shared_content_receiver')]
    public function dummy2(string $param1, string $param2): void
    {
    }

    #[Route('/agenda/{date}', name: 'agenda')]
    public function agenda(string $date): void
    {
    }
}
