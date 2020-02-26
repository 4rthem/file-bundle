<?php

namespace Arthem\Bundle\FileBundle\Doctrine;

use Arthem\Bundle\FileBundle\Model\ImageCrop;
use Arthem\Bundle\FileBundle\Model\ImageInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageCropManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    protected $fileClass;
    protected $imageCropClass;

    protected $cacheManager;
    protected $dataManager;
    protected $filterManager;

    protected $linkedFilters;

    public function __construct(EntityManagerInterface $em, $fileClass, $imageCropClass, CacheManager $cacheManager, DataManager $dataManager, FilterManager $filterManager, array $linkedFilters = [])
    {
        $this->em = $em;
        $this->fileClass = $fileClass;
        $this->imageCropClass = $imageCropClass;
        $this->cacheManager = $cacheManager;
        $this->dataManager = $dataManager;
        $this->filterManager = $filterManager;
        $this->linkedFilters = $linkedFilters;
    }

    /**
     * @param mixed $id
     *
     * @return ImageInterface
     */
    public function getImage($id)
    {
        $image = $this->em->find($this->fileClass, $id);
        if (null === $image) {
            throw new NotFoundHttpException('Image not found');
        }
        if (!$image instanceof ImageInterface) {
            throw new RuntimeException(sprintf('$image must implement %s', ImageInterface::class));
        }

        return $image;
    }

    /**
     * @param string $originFilter
     * @param string $filter
     *
     * @return ImageCrop
     */
    public function crop(ImageInterface $image, $originFilter, $filter, array $cropCoordinates)
    {
        $width = (float) $cropCoordinates['w'];
        $height = (float) $cropCoordinates['h'];
        $top = (float) $cropCoordinates['y'];
        $left = (float) $cropCoordinates['x'];

        $binary = $this->dataManager->find($originFilter, $image->getPath());

        $filterConfiguration = $this->filterManager->getFilterConfiguration();
        $originFilterConfig = $filterConfiguration->get($originFilter);
        $filterConfig = $filterConfiguration->get($filter);

        if (!isset($originFilterConfig['filters']['thumbnail']['size'])) {
            throw new InvalidArgumentException('Missing thumbnail filter for origin');
        }
        if (!isset($filterConfig['filters']['thumbnail']['size'])) {
            throw new InvalidArgumentException('Missing thumbnail filter');
        }

        /** @var ImageCrop[] $crops */
        $crops = [];
        if (isset($this->linkedFilters[$filter])) {
            foreach ($this->linkedFilters[$filter] as $cascadeFilter) {
                $this->makeCrop($image, $binary, $cascadeFilter, $originFilter, $left, $top, $width, $height);

                $crops[] = $this->getCrop($image, $cascadeFilter);
                $image->setCropDate($cascadeFilter, time());
            }
        }

        $this->makeCrop($image, $binary, $filter, $originFilter, $left, $top, $width, $height);

        $crops[] = $crop = $this->getCrop($image, $filter);

        foreach ($crops as $c) {
            $c->setTop($top)
                ->setLeft($left)
                ->setWidth($width)
                ->setHeight($height);
            $this->em->persist($c);
        }

        $image->setCropDate($filter, time());

        $this->em->persist($image);
        $this->em->flush();

        return $crop;
    }

    private function makeCrop(ImageInterface $image, BinaryInterface $binary, $filter, $originFilter, $left, $top, $width, $height)
    {
        $filteredBinary = $this->filterManager->applyFilter($binary, $originFilter);
        $filteredBinary = $this->filterManager->apply($filteredBinary, [
            'filters' => [
                'crop' => [
                    'start' => [$left, $top],
                    'size' => [$width, $height],
                ],
            ],
        ]);
        $filteredBinary = $this->filterManager->applyFilter($filteredBinary, $filter);
        $this->cacheManager->remove($image->getPath(), $filter);
        $this->cacheManager->store($filteredBinary, $image->getPath(), $filter);
    }

    private function getCrop(ImageInterface $image, $filter)
    {
        $crop = $this->em->getRepository($this->imageCropClass)->findOneBy([
            'filterName' => $filter,
            'file' => $image->getId(),
        ]);

        if (null === $crop) {
            /** @var ImageCrop $crop */
            $crop = new $this->imageCropClass();
            $crop->setFile($image)
                ->setFilterName($filter);
        }

        return $crop;
    }
}
