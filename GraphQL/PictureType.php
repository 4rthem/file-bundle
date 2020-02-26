<?php

declare(strict_types=1);

namespace Arthem\Bundle\FileBundle\GraphQL;

use Youshido\GraphQL\Type\Object\AbstractObjectType;
use Youshido\GraphQL\Type\Scalar\StringType;

class PictureType extends AbstractObjectType
{
    /**
     * {@inheritdoc}
     */
    public function build($config)
    {
        $config->addFields([
            'url' => [
                'type' => new StringType(),
            ],
        ]);
    }
}
