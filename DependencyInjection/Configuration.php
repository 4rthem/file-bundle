<?php

namespace Arthem\Bundle\FileUploadBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('arthem_file_upload');

		$rootNode
			->children()
				->scalarNode('db_driver')->defaultValue('orm')->end()
				->arrayNode('model')
					->addDefaultsIfNotSet()
					->children()
						->scalarNode('file_class')->defaultValue('Arthem\Bundle\FileUploadBundle\Model\File')->cannotBeEmpty()->end()
						->scalarNode('file_table')->defaultValue('files')->cannotBeEmpty()->end()
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
                                        ->scalarNode('image_crop_class')->defaultValue('Arthem\Bundle\FileUploadBundle\Model\ImageCrop')->cannotBeEmpty()->end()
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
