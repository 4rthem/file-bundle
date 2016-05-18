<?php

namespace Arthem\Bundle\FileBundle\Twig\Extension;

use Arthem\Bundle\FileBundle\Model\FileInterface;
use Arthem\Bundle\FileBundle\ImageManager;

class ImageExtension extends \Twig_Extension
{
    /**
     * @var ImageManager
     */
    private $imageManager;

    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'image';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('image', [$this, 'image']),
            new \Twig_SimpleFilter('image_placeholder', [$this, 'imagePlaceholder']),
        ];
    }

    public function image($object, $field, $filter = null)
    {
        if ($object instanceof FileInterface) {
            return $this->imageManager->getImagePath($object, $filter);
        }

        return $this->imageManager->imagePath($object, $field, $filter);
    }

    public function imagePlaceholder($object, $field, $filter)
    {
        return $this->imageManager->imagePlaceholder($object, $field, $filter);
    }
}
