<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\ApiPlatform\Bridge\Doctrine\MongoDB;

use AppBundle\ApiPlatform\Bridge\Doctrine\MongoDB\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\IdentifierManagerTrait;

/**
 * Item data provider for the Doctrine MongoDB ODM.
 */
class ItemDataProvider implements ItemDataProviderInterface
{
    use IdentifierManagerTrait;

    private $managerRegistry;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $itemExtensions;
    private $decorated;

    /**
     * @param ManagerRegistry                        $managerRegistry
     * @param PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory
     * @param PropertyMetadataFactoryInterface       $propertyMetadataFactory
     * @param QueryItemExtensionInterface[]          $itemExtensions
     * @param ItemDataProviderInterface|null         $decorated
     */
    public function __construct(ManagerRegistry $managerRegistry, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, array $itemExtensions = [], ItemDataProviderInterface $decorated = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemExtensions = $itemExtensions;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        if ($this->decorated) {
            try {
                return $this->decorated->getItem($resourceClass, $id, $operationName, $context);
            } catch (ResourceClassNotSupportedException $resourceClassNotSupportedException) {
                // Ignore it
            }
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $manager) {
            throw new ResourceClassNotSupportedException();
        }

        if ( (!isset($context['fetch_data'])) || ($manager instanceof DocumentManager) ) {
            return $manager->getReference($resourceClass, $id);
        }

        /** @var DocumentRepository $repository */
        $repository = $manager->getRepository($resourceClass);
        $queryBuilder = $repository->createQueryBuilder();

        try {
            $identifiers = $this->normalizeIdentifiers($id, $manager, $resourceClass);
        } catch (PropertyNotFoundException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

            foreach ($identifiers as $propertyName => $value) {
            $queryBuilder
                ->field($propertyName)->equals($value);
        }

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $resourceClass, $id, $operationName);
        }

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
