<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Exporter\Plugin;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Model\CodeAwareInterface;

final class ProductResourcePlugin extends ResourcePlugin
{
    /**
     * {@inheritdoc}
     */
    public function init(array $idsToExport): void
    {
        parent::init($idsToExport);

        /** @var ProductInterface $resource */
        foreach ($this->resources as $resource) {
            if (null !== $resource->getMainTaxon()) {
                $this->addDataForResource($resource, 'MainTaxon', $resource->getMainTaxon()->getCode());
            }

            $translations = [];
            /** @var ProductTranslationInterface $translation */
            foreach ($resource->getTranslations() as $translation) {
                $translations[$translation->getLocale()] = [
                    'Name' => $translation->getName(),
                    'Slug' => $translation->getSlug(),
                    'Description' => $translation->getDescription(),
                    'ShortDescription' => $translation->getShortDescription(),
                    'MetaKeywords' => $translation->getMetaKeywords(),
                    'MetaDescription' => $translation->getMetaDescription(),
                ];
            }

            $this->addDataForResource($resource, 'Translations', $translations);

            $this->addDataForResource($resource, 'Taxons', array_map(function (ProductTaxonInterface $productTaxon): array {
                return [
                    'Taxon' => $this->getPossibleResourceCodeValue($productTaxon->getTaxon()),
                    'Position' => $productTaxon->getPosition(),
                ];
            }, $resource->getProductTaxons()->toArray()));

            $this->addDataForResource($resource, 'Channels', array_map(function (ChannelInterface $channel): ?string {
                return $this->getPossibleResourceCodeValue($channel);
            }, $resource->getChannels()->toArray()));

            $this->addDataForResource($resource, 'Images', array_map(function (ProductImageInterface $productImage): array {
                return [
                    'Type' => $productImage->getType(),
                    'Path' => $productImage->getPath(),
                ];
            }, $resource->getImages()->toArray()));

            $this->addDataForResource($resource, 'Associations', array_map(function (ProductAssociationInterface $productAssociation): array {
                return [
                    'Type' => $this->getPossibleResourceCodeValue($productAssociation->getType()),
                    'Products' => array_map(function (ProductInterface $product): ?string {
                        return $this->getPossibleResourceCodeValue($product);
                    }, $productAssociation->getAssociatedProducts()->toArray()),
                ];
            }, $resource->getAssociations()->toArray()));

            $this->addDataForResource($resource, 'Attributes', array_map(function (ProductAttributeValueInterface $attributeValue): array {
                return [
                    'Attribute' => $this->getPossibleResourceCodeValue($attributeValue->getAttribute()),
                    'Locale' => $attributeValue->getLocaleCode(),
                    'Value' => $attributeValue->getValue(),
                ];
            }, $resource->getAttributes()->toArray()));

            $this->addDataForResource($resource, 'Options', array_map(function (ProductOptionInterface $productOption): ?string {
                return $this->getPossibleResourceCodeValue($productOption);
            }, $resource->getOptions()->toArray()));

            $this->addDataForResource($resource, 'Variants', array_map(function (ProductVariantInterface $productVariant): array {
                $translations = [];

                /** @var ProductVariantTranslationInterface $translation */
                foreach ($productVariant->getTranslations() as $translation) {
                    $translations[$translation->getLocale()] = [
                        'Name' => $translation->getName(),
                    ];
                }

                $channelPricings = [];

                /** @var ChannelPricingInterface $channelPricing */
                foreach ($productVariant->getChannelPricings() as $channelPricing) {
                    $channelPricings[$channelPricing->getChannelCode()] = [
                        'Price' => $channelPricing->getPrice(),
                        'OriginalPrice' => $channelPricing->getOriginalPrice(),
                    ];
                }

                return [
                    'Code' => $productVariant->getCode(),
                    'Position' => $productVariant->getPosition(),
                    'Version' => $productVariant->getVersion(),
                    'OnHold' => $productVariant->getOnHold(),
                    'OnHand' => $productVariant->getOnHand(),
                    'Tracked' => $productVariant->isTracked(),
                    'Width' => $productVariant->getWidth(),
                    'Height' => $productVariant->getHeight(),
                    'Depth' => $productVariant->getDepth(),
                    'Weight' => $productVariant->getWeight(),
                    'ShippingRequired' => $productVariant->isShippingRequired(),
                    'TaxCategory' => $this->getPossibleResourceCodeValue($productVariant->getTaxCategory()),
                    'ShippingCategory' => $this->getPossibleResourceCodeValue($productVariant->getShippingCategory()),
                    'Images' => array_map(function (ProductImageInterface $productImage): ?string {
                        return $productImage->getPath();
                    }, $productVariant->getImages()->toArray()),
                    'Translations' => $translations,
                    'ChannelPricings' => $channelPricings,
                    'Options' => array_map(function (ProductOptionValueInterface $productOptionValue): ?string {
                        return $this->getPossibleResourceCodeValue($productOptionValue);
                    }, $productVariant->getOptionValues()->toArray()),
                ];
            }, $resource->getVariants()->toArray()));
        }
    }

    private function getPossibleResourceCodeValue(?CodeAwareInterface $codeAware): ?string
    {
        return null !== $codeAware ? $codeAware->getCode() : null;
    }
}
