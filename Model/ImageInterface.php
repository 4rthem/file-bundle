<?php

namespace Arthem\Bundle\FileBundle\Model;

interface ImageInterface extends FileInterface
{
    /**
     * @param string $filterName
     *
     * @return ImageCrop
     */
    public function getImageCrop($filterName);

    /**
     * Return the last modified crop timestamp for a given filter.
     *
     * @param string $filterName
     *
     * @return int|null
     */
    public function getCropDate($filterName);

    /**
     * @param string $filterName
     * @param int    $date       A timestamp
     */
    public function setCropDate($filterName, $date);
}
