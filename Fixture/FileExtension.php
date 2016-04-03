<?php

namespace Arthem\Bundle\FileUploadBundle\Fixture;

use Arthem\Bundle\FixturesBundle\Extension\FixtureExtension;
use Arthem\Bundle\FixturesBundle\Fixtures\FixtureData;
use Gedmo\Uploadable\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileExtension extends FixtureExtension
{
    function __construct($webDir)
    {
        chdir($webDir);
    }

    public function getTransformers()
    {
        return [
            'upload' => 'transformUpload',
        ];
    }

    public function transformUpload($value, FixtureData $context)
    {
        $mimeTypeGuesser = new MimeTypeGuesser;

        $currentDirectory = dirname($context->getSrc());
        $path             = $currentDirectory . '/' . $value;
        if (!$src = realpath($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist', $path));
        }

        $file = new UploadedFile($src, basename($value), $mimeTypeGuesser->guess($src), filesize($src), null, true);

        return $file;
    }
}
