<?php

declare(strict_types=1);

namespace Arthem\Bundle\FileBundle\Model;

interface FileWrapperInterface
{
    public function getWrappedFile(): FileInterface;
}
