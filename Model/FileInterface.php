<?php

namespace Arthem\Bundle\FileBundle\Model;

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
    public function getFile();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @param string $path
     */
    public function setPath($path);

    /**
     * @return bool
     */
    public function isPlaceholder();

    /**
     * @param bool $isPlaceholder
     */
    public function setPlaceholder($isPlaceholder);

    /**
     * @param string $mimeType
     */
    public function setMimeType($mimeType);

    /**
     * @return string
     */
    public function getMimeType();

    /**
     * Security for file upload
     * Ensure that only the uploader can attach the File to an object.
     *
     * @return string
     */
    public function getToken();

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token);

    public function setUserId(string $userId): void;

    public function getUserId(): ?string;

    /**
     * @return float
     */
    public function getSize();
}
