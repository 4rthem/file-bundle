<?php

namespace Arthem\Bundle\FileBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * Forward compatibility class in case FOSUserBundle is used with older
 * versions of Symfony2 or the doctrine bundles that do not provide the
 * register mappings compiler pass yet.
 *
 * @author David Buchmann <david@liip.ch>
 */
class RegisterMappingsPass implements CompilerPassInterface
{
    private $driver;
    private $driverPattern;
    private $namespaces;
    private $enabledParameter;
    private $fallbackManagerParameter;

    public function __construct($driver, $driverPattern, $namespaces, $enabledParameter, $fallbackManagerParameter)
    {
        $this->driver = $driver;
        $this->driverPattern = $driverPattern;
        $this->namespaces = $namespaces;
        $this->enabledParameter = $enabledParameter;
        $this->fallbackManagerParameter = $fallbackManagerParameter;
    }

    /**
     * Register mappings with the metadata drivers.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter($this->enabledParameter)) {
            return;
        }

        $chainDriverDefService = $this->getChainDriverServiceName($container);
        $chainDriverDef = $container->getDefinition($chainDriverDefService);
        foreach ($this->namespaces as $namespace) {
            $chainDriverDef->addMethodCall('addDriver', [$this->driver, $namespace]);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws ParameterNotFoundException
     *
     * @return string
     */
    protected function getChainDriverServiceName(ContainerBuilder $container)
    {
        foreach (['arthem_base.model_manager_name', $this->fallbackManagerParameter] as $param) {
            if ($container->hasParameter($param)) {
                $name = $container->getParameter($param);
                if ($name) {
                    return sprintf($this->driverPattern, $name);
                }
            }
        }

        throw new ParameterNotFoundException('None of the managerParameters resulted in a valid name');
    }

    public static function createOrmMappingDriver(array $mappings, $bundleShortName)
    {
        $arguments = [$mappings, '.orm.yml'];
        $locator = new Definition('Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator', $arguments);
        $driver = new Definition('Doctrine\ORM\Mapping\Driver\YamlDriver', [$locator]);

        return new self($driver, 'doctrine.orm.%s_metadata_driver', $mappings, $bundleShortName.'.backend_type_orm', 'doctrine.default_entity_manager');
    }

    public static function createMongoDBMappingDriver($mappings, $bundleShortName)
    {
        $arguments = [$mappings, '.mongodb.yml'];
        $locator = new Definition('Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator', $arguments);
        $driver = new Definition('Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver', [$locator]);

        return new self($driver, 'doctrine_mongodb.odm.%s_metadata_driver', $mappings, $bundleShortName.'.backend_type_mongodb', 'doctrine_mongodb.odm.default_document_manager');
    }

    public static function createCouchDBMappingDriver($mappings, $bundleShortName)
    {
        $arguments = [$mappings, '.couchdb.yml'];
        $locator = new Definition('Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator', $arguments);
        $driver = new Definition('Doctrine\ODM\CouchDB\Mapping\Driver\YamlDriver', [$locator]);

        return new self($driver, 'doctrine_couchdb.odm.%s_metadata_driver', $mappings, $bundleShortName.'.backend_type_couchdb', 'doctrine_couchdb.default_document_manager');
    }
}
