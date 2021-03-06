<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Medialeads\ApiPlatformMongoDbBridge\DependencyInjection;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


/**
 * The extension of this bundle.
 *
 */
final class MedialeadsApiPlatformMongoDbBridgeExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        /*$configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);*/

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $bundles = $container->getParameter('kernel.bundles');

        $this->getResourcesToWatch($container, $bundles);
    }

    /**
     * On ajoute le répertoire /Document
     *
     * @param ContainerBuilder $container
     * @param array $bundles
     */
    private function getResourcesToWatch(ContainerBuilder $container, array $bundles)
    {
        $resourceClassDirectories = [];

        foreach ($bundles as $bundle) {
            $bundleDirectory = dirname((new \ReflectionClass($bundle))->getFileName());

            if (file_exists($entityDirectory = $bundleDirectory.'/Document')) {
                $resourceClassDirectories[] = $entityDirectory;
                $container->addResource(new DirectoryResource($entityDirectory, '/\.php$/'));
            }
        }

        $container->setParameter('api_platform.resource_class_directories', $resourceClassDirectories);
    }
}
