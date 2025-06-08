<?php

namespace Plixus\TwigComponentPreviewBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PlixusTwigComponentPreviewBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}