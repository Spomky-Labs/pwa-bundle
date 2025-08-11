<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Controller;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class ProtocolHandlerController extends AbstractController
{
    #[Route('/lookup', name: 'protocol_lookup')]
    public function dummy1(): never
    {
        // This method is intentionally left empty.
        throw new RuntimeException('This method should not be called.');
    }

    #[Route('/store', name: 'protocol_store')]
    public function dummy2(): never
    {
        // This method is intentionally left empty.
        throw new RuntimeException('This method should not be called.');
    }
}
