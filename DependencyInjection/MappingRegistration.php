<?php


namespace Arthem\Bundle\FileBundle\DependencyInjection;

use Doctrine\Bundle\CouchDBBundle\DependencyInjection\Compiler\DoctrineCouchDBMappingsPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use Arthem\Bundle\FileBundle\DependencyInjection\Compiler\RegisterMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MappingRegistration
{
    static public function addRegisterMappingsPass(ContainerBuilder $container, $bundleDir, $bundleNamespace, $bundleShortName)
    {
        // the base class is only available since symfony 2.3
        $symfonyVersion = class_exists('Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterMappingsPass');

        $mappings = [
            realpath($bundleDir . '/Resources/config/doctrine/model') => $bundleNamespace . '\Model',
        ];

        if ($symfonyVersion && class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createYamlMappingDriver($mappings, ['arthem_base.model_manager_name'], $bundleShortName . '.backend_type_orm'));
        } else {
            $container->addCompilerPass(RegisterMappingsPass::createOrmMappingDriver($mappings, $bundleShortName));
        }

        if ($symfonyVersion && class_exists(DoctrineMongoDBMappingsPass::class)) {
            $container->addCompilerPass(DoctrineMongoDBMappingsPass::createYamlMappingDriver($mappings, ['arthem_base.model_manager_name'], $bundleShortName . '.backend_type_mongodb'));
        } else {
            $container->addCompilerPass(RegisterMappingsPass::createMongoDBMappingDriver($mappings, $bundleShortName));
        }

        if ($symfonyVersion && class_exists(DoctrineCouchDBMappingsPass::class)) {
            $container->addCompilerPass(DoctrineCouchDBMappingsPass::createYamlMappingDriver($mappings, ['arthem_base.model_manager_name'], $bundleShortName . '.backend_type_couchdb'));
        } else {
            $container->addCompilerPass(RegisterMappingsPass::createCouchDBMappingDriver($mappings, $bundleShortName));
        }
    }
}
