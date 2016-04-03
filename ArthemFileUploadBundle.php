<?php

namespace Arthem\Bundle\FileUploadBundle;

use Arthem\Bundle\BaseBundle\DependencyInjection\MappingRegistration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ArthemFileUploadBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        MappingRegistration::addRegisterMappingsPass($container, __DIR__, __NAMESPACE__, 'arthem_fileupload');
    }
}
