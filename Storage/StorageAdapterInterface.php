<?php

namespace Arthem\Bundle\FileBundle\Storage;

interface StorageAdapterInterface
{
    public function store(string $key, string $content);
}
