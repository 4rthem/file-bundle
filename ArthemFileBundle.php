<?php

namespace Arthem\Bundle\FileBundle;

use Arthem\Bundle\FileBundle\DependencyInjection\MappingRegistration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ArthemFileBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        MappingRegistration::addRegisterMappingsPass($container, __DIR__, __NAMESPACE__, 'arthem_file');
    }
}
