<?php


namespace Arthem\Bundle\FileBundle;

use Doctrine\Common\Util\ClassUtils;
use Arthem\Bundle\FileBundle\Model\FileInterface;
use Arthem\Bundle\FileBundle\Model\ImageInterface;
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

    function __construct(CacheManager $cacheManager, array $placeholders, $cropActive = false)
    {
        $this->cacheManager = $cacheManager;
        $this->placeholders = $placeholders;
        $this->cropActive   = $cropActive;
    }

    public function imagePath($object, $field, $filter)
    {
        $key = spl_object_hash($object) . '.' . $field . '.' . $filter;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $image = $object->{'get' . ucfirst($field)}();
        if ($image instanceof FileInterface) {
            $path = $image->getPath();
        } else {
            return $this->imagePlaceholder(get_class($object), $field, $filter);
        }

        if (null === $path) {
            return $this->cache[$key] = null;
        }

        $this->cache[$key] = $this->cacheManager->getBrowserPath($path, $filter);
        if ($this->cropActive && $image instanceof ImageInterface && (null !== $cropDate = $image->getCropDate($filter))) {
            $this->cache[$key] .= '?' . $cropDate;
        }

        return $this->cache[$key];
    }

    /**
     * @param object|string $class
     * @param string        $field
     * @param string        $filter
     * @return string
     */
    public function imagePlaceholder($class, $field, $filter)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $objectClass = ClassUtils::getRealClass($class);
        $key         = $objectClass . '.' . $field . '.' . $filter . '.placeholder';
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        if ($objectClass && isset($this->placeholders[$objectClass][$field])) {
            $path = $this->placeholders[$objectClass][$field];

            return $this->cache[$key] = $this->cacheManager->getBrowserPath($path, $filter);
        } else {
            throw new \InvalidArgumentException(sprintf('Placeholder is not defined for %s::%s', $objectClass, $field));
        }
    }

    public function getImagePath(FileInterface $image, $filter)
    {
        $key = '__' . spl_object_hash($image) . '.' . '.' . $filter;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $path = $image->getPath();

        $this->cache[$key] = $this->cacheManager->getBrowserPath($path, $filter);
        if ($this->cropActive && $image instanceof ImageInterface && (null !== $cropDate = $image->getCropDate($filter))) {
            $this->cache[$key] .= '?' . $cropDate;
        }

        return $this->cache[$key];
    }
} 
