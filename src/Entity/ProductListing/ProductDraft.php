<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMultiVendorMarketplacePlugin\Entity\ProductListing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Attribute\Model\AttributeSubjectInterface;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Webmozart\Assert\Assert;

class ProductDraft implements ResourceInterface, ProductDraftInterface
{
    protected ?int $id;

    protected string $code;

    protected bool $isVerified;

    protected string $status;

    protected ?\DateTimeInterface $verifiedAt;

    protected ?\DateTimeInterface $publishedAt;

    protected \DateTimeInterface $createdAt;

    protected int $versionNumber;

    /** @var Collection<int|string, ImageInterface> */
    protected Collection $images;

    /** @var Collection<int|string, ProductTranslationInterface> */
    protected Collection $translations;

    /** @var Collection<int|string, ProductListingPriceInterface> */
    protected Collection $productListingPrice;

    protected ProductListingInterface $productListing;

    /** @var Collection<int, AttributeValueInterface> */
    protected Collection $attributes;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->code = '';
        $this->status = ProductDraftInterface::STATUS_CREATED;
        $this->productListingPrice = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->isVerified = false;
        $this->createdAt = new \DateTime();
        $this->versionNumber = 1;
        $this->attributes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): void
    {
        $this->isVerified = $isVerified;
    }

    public function getVerifiedAt(): ?\DateTimeInterface
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeInterface $verifiedAt): void
    {
        $this->verifiedAt = $verifiedAt;
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeInterface $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(int $versionNumber): void
    {
        $this->versionNumber = $versionNumber;
    }

    /** @return Collection<int|string, ProductTranslationInterface> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslations(ProductTranslationInterface $translation): void
    {
        $this->translations->add($translation);
    }

    public function getProductListingPrice(): Collection
    {
        return $this->productListingPrice;
    }

    public function addProductListingPrice(ProductListingPriceInterface $productListingPrice): void
    {
        $this->productListingPrice->add($productListingPrice);
    }

    public function getProductListing(): ProductListingInterface
    {
        return $this->productListing;
    }

    public function setProductListing(ProductListingInterface $productListing): void
    {
        $this->productListing = $productListing;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function incrementVersion(): void
    {
        ++$this->versionNumber;
    }

    public function addTranslationsWithKey(ProductTranslationInterface $translation, string $key): void
    {
        $this->translations->set($key, $translation);
    }

    public function addProductListingPriceWithKey(ProductListingPriceInterface $productListingPrice, string $key): void
    {
        $this->productListingPrice->set($key, $productListingPrice);
    }

    public function accept(): void
    {
        $this->setStatus(ProductDraftInterface::STATUS_VERIFIED);
        $this->setVerifiedAt((new \DateTime()));
        $this->setIsVerified(true);
    }

    public function reject(): void
    {
        $this->setStatus(ProductDraftInterface::STATUS_REJECTED);
        $this->setVerifiedAt((new \DateTime()));
    }

    public function sendToVerification(): void
    {
        $this->setStatus(ProductDraftInterface::STATUS_UNDER_VERIFICATION);
        $this->setPublishedAt((new \DateTime()));
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function setImages(Collection $images): void
    {
        $this->images = $images;
    }

    public function addImage(ImageInterface $image): void
    {
        $this->images->add($image);
    }

    public function getAttributes(): Collection
    {
        /** @var Collection<int, AttributeValueInterface|null> $attributes */
        $attributes = $this->attributes;
        return $this->attributes;
    }

    public function getAttributesByLocale(
        string $localeCode,
        string $fallbackLocaleCode,
        ?string $baseLocaleCode = null
    ): Collection {
        if (null === $baseLocaleCode || $baseLocaleCode === $fallbackLocaleCode) {
            $baseLocaleCode = $fallbackLocaleCode;
            $fallbackLocaleCode = null;
        }

        $attributes = $this->attributes->filter(
            function (AttributeValueInterface $attribute) use ($baseLocaleCode) {
                return $attribute->getLocaleCode() === $baseLocaleCode || null === $attribute->getLocaleCode();
            }
        );

        $attributesWithFallback = [];

        /** @var DraftAttributeValueInterface $attribute */
        foreach ($attributes as $attribute) {
            $attributesWithFallback[] = $this->getAttributeInDifferentLocale($attribute, $localeCode, $fallbackLocaleCode);
        }

        /** @var Collection<int, AttributeValueInterface> $collection */
        $collection = new ArrayCollection($attributesWithFallback);
        return $collection;
    }

    public function addAttribute(?AttributeValueInterface $attribute): void
    {
        /** @var ProductAttributeValueInterface $attribute */
        if (!$this->hasAttribute($attribute)) {
            if($attribute instanceof DraftAttributeValueInterface) {
                $attribute->setDraft($this);
                $this->attributes->add($attribute);
            }
        }
    }

    public function removeAttribute(AttributeValueInterface $attribute): void
    {
        /** @var ProductAttributeValueInterface $attribute */
        Assert::isInstanceOf(
            $attribute,
            ProductAttributeValueInterface::class,
            'Attribute objects removed from a Product object have to implement ProductAttributeValueInterface'
        );

        if ($this->hasAttribute($attribute)) {
            $this->attributes->removeElement($attribute);
            if($attribute instanceof DraftAttributeValueInterface)
                $attribute->setDraft(null);
        }
    }

    public function hasAttribute(AttributeValueInterface $attribute): bool
    {
        return $this->attributes->contains($attribute);
    }

    public function hasAttributeByCodeAndLocale(string $attributeCode, ?string $localeCode = null): bool
    {
        foreach ($this->attributes as $attribute) {
            if(null === $attribute->getAttribute()){
                continue;
            }
            if ($attribute->getAttribute()->getCode() === $attributeCode
                && ($attribute->getLocaleCode() === $localeCode || null === $attribute->getLocaleCode())) {
                return true;
            }
        }

        return false;
    }

    public function getAttributeByCodeAndLocale(string $attributeCode, ?string $localeCode = null): ?AttributeValueInterface
    {
        foreach ($this->attributes as $attribute) {
            if (null === $attribute->getAttribute()){
                continue;
            }
            if ($attribute->getAttribute()->getCode() === $attributeCode &&
                ($attribute->getLocaleCode() === $localeCode || null === $attribute->getLocaleCode())) {
                return $attribute;
            }
        }

        return null;
    }

    protected function getAttributeInDifferentLocale(
        DraftAttributeValueInterface $attributeValue,
        string $localeCode,
        ?string $fallbackLocaleCode = null
    ): ?AttributeValueInterface {
        $attributeCode = $attributeValue->getCode();
        if (null === $attributeCode)
            return null;
        if (!$this->hasNotEmptyAttributeByCodeAndLocale($attributeCode, $localeCode)) {
            if (
                null !== $fallbackLocaleCode &&
                $this->hasNotEmptyAttributeByCodeAndLocale($attributeCode, $fallbackLocaleCode)
            ) {
                return $this->getAttributeByCodeAndLocale($attributeCode, $fallbackLocaleCode);
            }

            return $attributeValue;
        }
        /** @var AttributeValueInterface $attributes  */

        $attributes = $this->getAttributeByCodeAndLocale($attributeCode, $localeCode);
        return $attributes;

    }

    protected function hasNotEmptyAttributeByCodeAndLocale(string $attributeCode, string $localeCode): bool
    {
        $attributeValue = $this->getAttributeByCodeAndLocale($attributeCode, $localeCode);
        if (null === $attributeValue) {
            return false;
        }

        $value = $attributeValue->getValue();
        if ('' === $value || null === $value || [] === $value) {
            return false;
        }

        return true;
    }

    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
    }
}
