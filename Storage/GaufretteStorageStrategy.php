<?php

namespace Arthem\Bundle\FileBundle\Storage;

use League\Flysystem\FilesystemInterface;

class GaufretteStorageStrategy implements StorageAdapterInterface
{
    /**
     * @var FilesystemInterface
     */
    private $storageAdapter;

    public function __construct(FilesystemInterface $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function store(string $key, string $content)
    {
        $this->storageAdapter->write($key, $content);
    }
}
