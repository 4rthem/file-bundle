<?php

namespace Arthem\Bundle\FileBundle\DependencyInjection;

use Arthem\Bundle\FileBundle\ImageManager;
use Arthem\Bundle\FileBundle\LetterAvatar\AvatarGenerator;
use Arthem\Bundle\FileBundle\LetterAvatar\LetterAvatarManager;
use Arthem\Bundle\FileBundle\Storage\GaufretteStorageStrategy;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ArthemFileExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $container->setParameter('arthem_file.db_driver', $config['db_driver']);
        $container->setParameter('arthem_file.backend_type_'.$config['db_driver'], true);

        $this->mapModel($container, $config['model'], 'file');

        $drivers = [
            'orm' => 'doctrine',
            'mongodb' => 'doctrine_mongodb',
        ];
        $dbDriverService = $drivers[$config['db_driver']];
        $container->setAlias('arthem_file.manager_registry', $dbDriverService);

        $loader->load('services.yml');
        $loader->load('listener.yml');
        $loader->load('form.yml');

        if ($config['image']['enabled']) {
            $this->loadImage($container, $loader, $config['image']);
        }

        if ($config['storage']['enabled']) {
            $this->loadStorage($container, $loader, $config['storage']);
        }

        if ($config['letter_avatar']['enabled']) {
            $this->loadAvatar($container, $loader, $config['letter_avatar']);
        }

        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['ArthemFixturesBundle'])) {
            $loader->load('fixture.yml');
        }
    }

    public function loadStorage(ContainerBuilder $container, LoaderInterface $loader, array $config)
    {
        $definition = $container->getDefinition('arthem_file.listener.uploadable');
        $loader->load('storage.yml');
        $definition->addMethodCall('setStorageAdapter', [new Reference(GaufretteStorageStrategy::class)]);

        $gaufretteDef = $container->getDefinition(GaufretteStorageStrategy::class);
        $gaufretteDef->setArgument(0, new Reference(
            sprintf('oneup_flysystem.%s_filesystem', $config['gaufrette']['adapter'])
        ));
    }

    public function loadAvatar(ContainerBuilder $container, LoaderInterface $loader, array $config)
    {
        $loader->load('avatar.yml');
        $definition = $container->getDefinition(AvatarGenerator::class);
        $definition->setArgument('$colors', $config['colors']);
        $definition->setArgument('$font', $config['font']);

        $def = $container->getDefinition(ImageManager::class);
        $def->addMethodCall('setLetterAvatars', [
            new Reference(LetterAvatarManager::class),
            $config['mapping'],
        ]);
    }

    public function loadImage(ContainerBuilder $container, LoaderInterface $loader, array $config)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!isset($bundles['LiipImagineBundle'])) {
            throw new InvalidConfigurationException('LiipImagineBundle must be enabled in order to use image component.');
        }

        $loader->load('image.yml');
        $definition = $container->getDefinition(ImageManager::class);
        $definition->setArgument('$placeholders', $config['placeholders']);

        if ($config['crop']['enabled']) {
            $this->loadImageCrop($container, $loader, $config['crop']);
        }
    }

    public function loadImageCrop(ContainerBuilder $container, LoaderInterface $loader, array $config)
    {
        $this->mapModel($container, $config['model'], 'image_crop');

        $container->setParameter('arthem_file.crop.linked_filters', $config['linked_filters']);

        $def = $container->getDefinition('arthem_file.doctrine.listener.mapping');
        $def->addArgument('%arthem_file.model.image_crop.class%');
        $def->addArgument('%arthem_file.model.image_crop.table%');

        $def = $container->getDefinition(ImageManager::class);
        $def->replaceArgument('$cropActive', true);

        $loader->load('image_crop.yml');
    }

    private function mapModel(ContainerBuilder $container, array $model, $field)
    {
        $container->setParameter('arthem_file.model.'.$field.'.class', $model[$field.'_class']);
        $container->setParameter('arthem_file.model.'.$field.'.table', $model[$field.'_table']);
    }
}
