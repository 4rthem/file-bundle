<?php

declare(strict_types=1);

namespace Arthem\Bundle\FileBundle\GraphQL;

use Arthem\Bundle\FileBundle\ImageManager;
use Youshido\GraphQL\Execution\ResolveInfo;

class PictureResolver
{
    /**
     * @var ImageManager
     */
    private $imageManager;

    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function resolvePicture($value, array $args, ResolveInfo $info)
    {
        $filter = $args['filter'] ?? 'medium';

        return [
            'url' => $this->imageManager->imagePath($value, 'picture', $filter),
        ];
    }
}
