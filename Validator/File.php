<?php

namespace Arthem\Bundle\FileBundle\Validator;

use Symfony\Component\Validator\Constraints\File as BaseFile;

class File extends BaseFile
{
    public $maxSizeMessage = 'arthem_file.file.too_large';
    public $mimeTypesMessage = 'arthem_file.file.mime_type';

    public $uploadIniSizeErrorMessage = 'arthem_file.file.too_large';

    public $multiple = false;
}
