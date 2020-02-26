<?php

namespace Arthem\Bundle\FileBundle\Model;

use Ramsey\Uuid\Uuid;

class ImageCrop
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var FileInterface
     */
    protected $file;

    /**
     * @var string
     */
    protected $filterName;

    /**
     * @var float
     */
    protected $top;

    /**
     * @var float
     */
    protected $left;

    /**
     * @var float
     */
    protected $width;

    /**
     * @var float
     */
    protected $height;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setFile(FileInterface $file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return FileInterface
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param string $filterName
     *
     * @return $this
     */
    public function setFilterName($filterName)
    {
        $this->filterName = $filterName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilterName()
    {
        return $this->filterName;
    }

    /**
     * @param float $left
     *
     * @return $this
     */
    public function setLeft($left)
    {
        $this->left = $left;

        return $this;
    }

    /**
     * @return float
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param float $top
     *
     * @return $this
     */
    public function setTop($top)
    {
        $this->top = $top;

        return $this;
    }

    /**
     * @return float
     */
    public function getTop()
    {
        return $this->top;
    }

    /**
     * @param float $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param float $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    public function getCoords()
    {
        return implode(',', [
            $this->getWidth(),
            $this->getHeight(),
            $this->getTop(),
            $this->getLeft(),
        ]);
    }
}
