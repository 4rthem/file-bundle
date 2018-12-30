<?php

namespace Arthem\Bundle\FileBundle\DependencyInjection;

use Arthem\Bundle\FileBundle\Model\File;
use Arthem\Bundle\FileBundle\Model\ImageCrop;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('arthem_file');

        $rootNode
            ->children()
                ->scalarNode('db_driver')->defaultValue('orm')->end()
                ->arrayNode('model')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('file_class')->defaultValue(File::class)->cannotBeEmpty()->end()
                        ->scalarNode('file_table')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('storage')
                    ->canBeEnabled()
                    ->children()
                        ->arrayNode('gaufrette')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('adapter')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('letter_avatar')
                    ->canBeEnabled()
                    ->children()
                        ->arrayNode('mapping')
                            ->defaultValue([])
                            ->example('Acme\DemoBundle\Entity\User: displayName')
                            ->prototype('array')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('font')->defaultValue('Proxima Nova, proxima-nova, HelveticaNeue-Light, Helvetica Neue Light, Helvetica Neue, Helvetica, Arial, Lucida Grande, sans-serif')->end()
                        ->scalarNode('colors')->defaultValue([
                            '#1abc9c',
                            '#16a085',
                            '#f1c40f',
                            '#f39c12',
                            '#2ecc71',
                            '#27ae60',
                            '#e67e22',
                            '#d35400',
                            '#3498db',
                            '#2980b9',
                            '#e74c3c',
                            '#c0392b',
                            '#9b59b6',
                            '#8e44ad',
                            '#bdc3c7',
                            '#34495e',
                            '#2c3e50',
                            '#95a5a6',
                            '#7f8c8d',
                            '#ec87bf',
                            '#d870ad',
                            '#f69785',
                            '#9ba37e',
                            '#b49255',
                            '#b49255',
                            '#a94136',
                        ])->end()
                    ->end()
                ->end()
                ->arrayNode('graphql')
                    ->canBeEnabled()
                    ->children()
                    ->end()
                ->end()
                ->arrayNode('image')
                    ->canBeDisabled()
                    ->children()
                        ->arrayNode('crop')
                            ->canBeEnabled()
                            ->children()
                                ->arrayNode('model')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('image_crop_class')->defaultValue(ImageCrop::class)->cannotBeEmpty()->end()
                                        ->scalarNode('image_crop_table')->defaultValue('image_crops')->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                                ->arrayNode('linked_filters')
                                    ->defaultValue([])
                                    ->example('small: [tiny]')
                                    ->prototype('array')
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('placeholders')
                            ->defaultValue([])
                            ->example('Acme\DemoBundle\Entity\User: { picture: "bundles/acmedemo/images/placeholders/user.png" }')
                            ->prototype('array')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
