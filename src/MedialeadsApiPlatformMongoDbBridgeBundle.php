<?php

namespace Medialeads\ApiPlatformMongoDbBridge;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Medialeads\ApiPlatformMongoDbBridge\DependencyInjection\Compiler\MongoDBExtensionPass;

class MedialeadsApiPlatformMongoDbBridgeBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MongoDBExtensionPass());
    }
}
