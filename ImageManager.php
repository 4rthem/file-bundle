<?php

namespace Arthem\Bundle\FileBundle;

use Arthem\Bundle\FileBundle\LetterAvatar\LetterAvatarManager;
use Arthem\Bundle\FileBundle\Model\FileInterface;
use Arthem\Bundle\FileBundle\Model\ImageInterface;
use Doctrine\Common\Util\ClassUtils;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class ImageManager
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var array
     */
    private $placeholders;

    private $cropActive;

    private $cache = [];

    private $letterAvatars = [];

    /**
     * @var LetterAvatarManager
     */
    private $avatarManager;

    public function __construct(CacheManager $cacheManager, array $placeholders, $cropActive = false)
    {
        $this->cacheManager = $cacheManager;
        $this->placeholders = $placeholders;
        $this->cropActive = $cropActive;
    }

    public function setLetterAvatars(LetterAvatarManager $avatarManager, array $letterAvatars)
    {
        $this->avatarManager = $avatarManager;
        $this->letterAvatars = $letterAvatars;
    }

    public function imagePath($object, $field, $filter)
    {
        $key = spl_object_hash($object).'.'.$field.'.'.$filter;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $image = $object->{'get'.ucfirst($field)}();
        $objectClass = ClassUtils::getRealClass(get_class($object));
        if ($image instanceof FileInterface) {
            $path = $image->getPath();
        } elseif (
            isset($this->letterAvatars[$objectClass], $this->letterAvatars[$objectClass][$field])
            && null !== $letterAvatarUrl = $this->getLetterAvatarUrl($object, $this->letterAvatars[$objectClass][$field])
        ) {
            return $this->cache[$key] = $letterAvatarUrl;
        } elseif ($this->isPlaceholderDefined($objectClass, $field)) {
            return $this->cache[$key] = $this->imagePlaceholder($objectClass, $field, $filter);
        } else {
            return $this->cache[$key] = null;
        }

        if (null === $path) {
            return $this->cache[$key] = null;
        }

        $this->cache[$key] = $this->cacheManager->getBrowserPath($path, $filter);
        if ($this->cropActive && $image instanceof ImageInterface && (null !== $cropDate = $image->getCropDate($filter))) {
            $this->cache[$key] .= '?'.$cropDate;
        }

        return $this->cache[$key];
    }

    public function getLetterAvatarUrl($object, string $textField): ?string
    {
        $text = $object->{'get'.ucfirst($textField)}();
        if (null === $text) {
            return null;
        }

        return $this->avatarManager->generatePath($text);
    }

    public function letterAvatar($object, string $field): ?string
    {
        $objectClass = ClassUtils::getRealClass(get_class($object));
        $key = spl_object_hash($object).'.'.$field.'.letter';
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        if ($objectClass && isset($this->letterAvatars[$objectClass][$field])) {
            return $this->cache[$key] = $this->getLetterAvatarUrl($object, $this->letterAvatars[$objectClass][$field]);
        } else {
            throw new \InvalidArgumentException(sprintf('Letter avatar is not defined for %s::%s', $objectClass, $field));
        }
    }

    /**
     * @param object|string $class
     * @param string        $field
     * @param string        $filter
     *
     * @return string
     */
    public function imagePlaceholder($class, $field, $filter)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $objectClass = ClassUtils::getRealClass($class);
        $key = $objectClass.'.'.$field.'.'.$filter.'.placeholder';
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        if ($this->isPlaceholderDefined($objectClass, $field)) {
            $path = $this->placeholders[$objectClass][$field];

            return $this->cache[$key] = $this->cacheManager->getBrowserPath($path, $filter);
        } else {
            throw new \InvalidArgumentException(sprintf('Placeholder is not defined for %s::%s', $objectClass, $field));
        }
    }

    private function isPlaceholderDefined($objectClass, string $field): bool
    {
        return $objectClass && isset($this->placeholders[$objectClass][$field]);
    }

    public function getImagePath(FileInterface $image, $filter)
    {
        $key = '__'.spl_object_hash($image).'.'.'.'.$filter;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $path = $image->getPath();

        $this->cache[$key] = $this->cacheManager->getBrowserPath($path, $filter);
        if ($this->cropActive && $image instanceof ImageInterface && (null !== $cropDate = $image->getCropDate($filter))) {
            $this->cache[$key] .= '?'.$cropDate;
        }

        return $this->cache[$key];
    }
}
