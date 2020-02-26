<?php

namespace Arthem\Bundle\FileBundle\Storage\PathStrategy;

use Arthem\Bundle\FileBundle\Model\FileInterface;

interface PathStrategyInterface
{
    public function getPath(FileInterface $file): string;
}
