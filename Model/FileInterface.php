<?php

namespace Arthem\Bundle\FileUploadBundle\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @param array|UploadedFile $file
     */
    public function setFile($file);

    /**
     * @return UploadedFile
     */
    function getFile();

    /**
     * @return string
     */
    function getPath();

    /**
     * @param string $path
     */
    function setPath($path);

    /**
     * @return boolean
     */
    function isPlaceholder();

    /**
     * @param boolean $isPlaceholder
     */
    function setPlaceholder($isPlaceholder);

    /**
     * @param string $mimeType
     */
    function setMimeType($mimeType);

    /**
     * @return string
     */
    function getMimeType();

    /**
     * Security for file upload
     * Ensure that only the uploader can attach the File to an object
     *
     * @return string
     */
    function getToken();

    /**
     * @param string $token
     * @return $this
     */
    function setToken($token);
}
