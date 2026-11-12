<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

// The kernel is infrastructure: it wires the container and the bundles,
// the domain stays ignorant of it.
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
