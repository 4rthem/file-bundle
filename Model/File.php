<?php

namespace Arthem\Bundle\FileBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class File implements FileInterface, ImageInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * Uploaded file info from $_FILES
     * Not mapped with Doctrine.
     *
     * @var array
     */
    protected $file;

    /**
     * @var ImageCrop[]|ArrayCollection
     */
    protected $crops;

    /**
     * @var array
     */
    protected $cropDates;

    /**
     * @var string The folder where to store the files
     */
    private $context;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $originalFilename;

    /**
     * @var string
     */
    protected $extension;

    /**
     * @var string
     */
    protected $mimeType;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string|null
     */
    protected $userId;

    /**
     * @var float
     */
    protected $size;

    /**
     * @var bool
     */
    protected $isPlaceholder;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    public function __construct()
    {
        $this->crops = new ArrayCollection();
        $this->id = Uuid::uuid4();
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param array $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return array|UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if ($this->path) {
            return $this->path;
        }

        return 'uploads/'.($this->context ? $this->context.'/' : '').date('Y/m/d').'/';
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function setContext($context)
    {
        if (!preg_match('#^[a-z0-9_\-]+$#i', $context)) {
            throw new \InvalidArgumentException('Invalid context format.');
        }
        $this->context = $context;
    }

    protected function getContext()
    {
        return $this->context;
    }

    public function callbackMethod(array $file)
    {
        $this->originalFilename = $file['origFileName'];
        $this->extension = substr($file['fileExtension'], 1);
    }

    public function isPlaceholder()
    {
        return $this->isPlaceholder;
    }

    public function setPlaceholder($isPlaceholder)
    {
        $this->isPlaceholder = $isPlaceholder;
    }

    /**
     * Security for file upload
     * Ensure that only the uploader can attach the File to an object.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * @param string $filterName
     *
     * @return ImageCrop
     */
    public function getImageCrop($filterName)
    {
        if (null !== $this->crops) {
            foreach ($this->crops as $crop) {
                if ($crop->getFilterName() === $filterName) {
                    return $crop;
                }
            }
        }
    }

    public function getCropDate($filterName)
    {
        if (isset($this->cropDates[$filterName])) {
            return $this->cropDates[$filterName];
        }
    }

    public function setCropDate($filterName, $date)
    {
        $this->cropDates[$filterName] = $date;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function setSize(float $size): void
    {
        $this->size = $size;
    }

    /**
     * Used to return path to the ImageValidator.
     *
     * @see \Symfony\Component\Validator\Constraints\ImageValidator
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->file instanceof UploadedFile) {
            return $this->file->getRealPath();
        }

        return $this->path ?: '';
    }
}
