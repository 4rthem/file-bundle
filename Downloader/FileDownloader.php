<?php

namespace Arthem\Bundle\FileBundle\Downloader;

use Arthem\Bundle\FileBundle\Model\FileInterface;
use Gedmo\Uploadable\MimeType\MimeTypesExtensionsMap;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileDownloader
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var UploadableManager
     */
    private $uploadableManager;

    /**
     * @var string
     */
    private $fileClass;

    /**
     * @var string
     */
    private $tempDir;

    function __construct(ClientInterface $client, UploadableManager $uploadableManager, $fileClass, $tempDir = null)
    {
        $this->client = $client;
        $this->uploadableManager = $uploadableManager;
        $this->fileClass = $fileClass;
        $this->tempDir = $tempDir ?: sys_get_temp_dir();
    }

    public function download($uri)
    {
        $request = new Request('GET', $uri);
        $response = $this->client->send($request);

        $mimeType = $response->getHeader('Content-Type')[0];

        if (isset(MimeTypesExtensionsMap::$map[$mimeType])) {
            $extension = MimeTypesExtensionsMap::$map[$mimeType];
        } else {
            $extension = 'unknown';
        }

        $tmpFile = $this->tempDir . '/' . uniqid() . '.' . $extension;
        file_put_contents($tmpFile, $response->getBody()->getContents());

        $fileInfo = new UploadedFile($tmpFile, basename($tmpFile), $mimeType, filesize($tmpFile), null, true);

        /** @var FileInterface $file */
        $file = new $this->fileClass;
        $this->uploadableManager->markEntityToUpload($file, $fileInfo);

        return $file;
    }
}
