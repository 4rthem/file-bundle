<?php


namespace Arthem\Bundle\FileBundle\Validator;

use Symfony\Component\Validator\Constraints\File as BaseFile;

class File extends BaseFile
{
    public $maxSizeMessage = 'arthem_fileupload.file.too_large';
    public $mimeTypesMessage = 'arthem_fileupload.file.mime_type';

    public $uploadIniSizeErrorMessage = 'arthem_fileupload.file.too_large';

    public $multiple = false;
} 
