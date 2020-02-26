<?php

namespace Arthem\Bundle\FileBundle\Twig\Extension;

use Arthem\Bundle\FileBundle\ImageManager;
use Arthem\Bundle\FileBundle\Model\FileInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ImageExtension extends AbstractExtension
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
            new TwigFilter('image', [$this, 'image']),
            new TwigFilter('image_placeholder', [$this, 'imagePlaceholder']),
            new TwigFilter('letter_avatar', [$this, 'letterAvatar']),
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

    public function letterAvatar($object, string $field): ?string
    {
        return $this->imageManager->letterAvatar($object, $field);
    }
}
