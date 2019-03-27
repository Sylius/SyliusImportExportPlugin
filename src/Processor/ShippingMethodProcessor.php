<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Processor;

use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Model\ShippingCategoryInterface;
use Sylius\Component\Taxation\Model\TaxCategoryInterface;

final class ShippingMethodProcessor implements ResourceProcessorInterface
{
    /** @var FactoryInterface */
    private $shippingMethodFactory;

    /** @var RepositoryInterface */
    private $shippingMethodRepository;

    /** @var RepositoryInterface */
    private $zoneRepository;

    /** @var RepositoryInterface */
    private $categoryRepository;

    /** @var RepositoryInterface */
    private $taxCategoryRepository;

    /** @var ObjectManager */
    private $manager;

    /** @var MetadataValidatorInterface */
    private $metadataValidator;

    /** @var string[] */
    private $headerKeys;

    public function __construct(
        FactoryInterface $shippingMethodFactory,
        RepositoryInterface $shippingMethodRepository,
        RepositoryInterface $zoneRepository,
        RepositoryInterface $categoryRepository,
        RepositoryInterface $taxCategoryRepository,
        ObjectManager $manager,
        MetadataValidatorInterface $metadataValidator,
        array $headerKeys
    ) {
        $this->shippingMethodFactory = $shippingMethodFactory;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->zoneRepository = $zoneRepository;
        $this->categoryRepository = $categoryRepository;
        $this->taxCategoryRepository = $taxCategoryRepository;
        $this->manager = $manager;
        $this->metadataValidator = $metadataValidator;
        $this->headerKeys = $headerKeys;
    }

    public function process(array $data): void
    {
        $this->metadataValidator->validateHeaders($this->headerKeys, $data);

        /** @var ShippingMethodInterface $shippingMethod */
        $shippingMethod = $this->getShippingMethod($data['Code']);
        $shippingMethod->setZone($this->findZone($data['Zone']));
        $shippingMethod->setCategory($this->findCategory($data['Category']));
        $shippingMethod->setCalculator($data['Calculator']);
        $shippingMethod->setEnabled($data['Enabled']);
        $shippingMethod->setConfiguration($data['Configuration']);
        $shippingMethod->setPosition($data['Position']);
        $shippingMethod->setCategoryRequirement($data['CategoryRequirement']);
        $shippingMethod->setTaxCategory($this->findTaxCategory($data['TaxCategory']));

        foreach ($data['Translations'] as $locale => $translation) {
            $shippingMethod->setCurrentLocale($locale);
            $shippingMethod->setFallbackLocale($locale);

            $shippingMethod->setName($translation['Name']);
            $shippingMethod->setDescription($translation['Description']);
        }

        $this->manager->flush();
    }

    private function getShippingMethod(?string $code): ?ShippingMethodInterface
    {
        if ($code === null || $code === '') {
            return null;
        }

        $shippingMethod = $this->findShippingMethod($code);

        if ($shippingMethod === null) {
            /** @var ShippingMethodInterface $shippingMethod */
            $shippingMethod = $this->shippingMethodFactory->createNew();
            $shippingMethod->setCode($code);

            $this->saveShippingMethod($shippingMethod);
        }

        return $shippingMethod;
    }

    private function findShippingMethod(?string $code): ?ShippingMethodInterface
    {
        /** @var ShippingMethodInterface|null $shippingMethod */
        $shippingMethod = $this->shippingMethodRepository->findOneBy(['code' => $code]);

        return $shippingMethod;
    }

    private function saveShippingMethod(ShippingMethodInterface $shippingMethod): void
    {
        $this->manager->persist($shippingMethod);
    }

    private function findZone(?string $code): ?ZoneInterface
    {
        /** @var ZoneInterface|null $zone */
        $zone = $this->zoneRepository->findOneBy(['code' => $code]);

        return $zone;
    }

    private function findCategory(?string $code): ?ShippingCategoryInterface
    {
        /** @var ShippingCategoryInterface|null $shippingCategory */
        $shippingCategory = $this->categoryRepository->findOneBy(['code' => $code]);

        return $shippingCategory;
    }

    private function findTaxCategory(?string $code): ?TaxCategoryInterface
    {
        /** @var TaxCategoryInterface|null $taxCategory */
        $taxCategory = $this->taxCategoryRepository->findOneBy(['code' => $code]);

        return $taxCategory;
    }
}