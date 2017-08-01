<?php

namespace Arthem\Bundle\FileBundle\EventListener;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Uploadable\Mapping\Validator;
use Gedmo\Uploadable\UploadableListener;

class GedmoUploadableListener extends UploadableListener
{
    public function doMoveFile($source, $dest, $isUploadedFile = true)
    {
        $path = self::getUploadTmpDir() . '/' . $dest;
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return parent::doMoveFile($source, $path, $isUploadedFile);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPath(ClassMetadata $meta, array $config, $object)
    {
        $originalValue = Validator::$validateWritableDirectory;
        Validator::$validateWritableDirectory = false;
        $return = parent::getPath($meta, $config, $object);
        Validator::$validateWritableDirectory = $originalValue;

        return $return;
    }

    static public function getUploadTmpDir(): string
    {
        return sys_get_temp_dir() . '/app-upload';
    }
}
