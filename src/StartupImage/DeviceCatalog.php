<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

/**
 * The devices a startup image is generated for.
 *
 * iOS only shows a startup image whose dimensions match the screen exactly, so an entry is needed for every
 * distinct point size Apple ships. Two devices sharing a point size and a pixel ratio are one entry here:
 * their media queries would be identical, and the second image would never be picked.
 */
final readonly class DeviceCatalog
{
    /**
     * @return list<Device>
     */
    public function all(): array
    {
        return [
            // iPads
            Device::create('iPad Pro 13" (M4)', 1032, 1376, 2),
            Device::create('iPad Pro 11" (M4)', 834, 1210, 2),
            Device::create('iPad Pro 12.9"', 1024, 1366, 2),
            Device::create('iPad Pro 11"', 834, 1194, 2),
            Device::create('iPad Pro 10.5"', 834, 1112, 2),
            Device::create('iPad Air 10.9"', 820, 1180, 2),
            Device::create('iPad 10.2"', 810, 1080, 2),
            Device::create('iPad mini 8.3"', 744, 1133, 2),
            Device::create('iPad 9.7"', 768, 1024, 2),

            // iPhones
            Device::create('iPhone 16 Pro Max', 440, 956, 3),
            Device::create('iPhone 16 Pro', 402, 874, 3),
            Device::create('iPhone 15 Pro Max', 430, 932, 3),
            Device::create('iPhone 15 Pro', 393, 852, 3),
            Device::create('iPhone 14 Plus', 428, 926, 3),
            Device::create('iPhone 14', 390, 844, 3),
            Device::create('iPhone 11 Pro Max', 414, 896, 3),
            Device::create('iPhone 11', 414, 896, 2),
            Device::create('iPhone 11 Pro', 375, 812, 3),
            Device::create('iPhone 8 Plus', 414, 736, 3),
            Device::create('iPhone 8', 375, 667, 2),
            Device::create('iPhone SE', 320, 568, 2),
        ];
    }

    /**
     * @return iterable<array{device: Device, orientation: Orientation}>
     */
    public function allOrientations(): iterable
    {
        foreach ($this->all() as $device) {
            foreach (Orientation::cases() as $orientation) {
                yield [
                    'device' => $device,
                    'orientation' => $orientation,
                ];
            }
        }
    }
}
