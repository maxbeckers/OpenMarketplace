<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMultiVendorMarketplacePlugin\Repository\ProductListing;

use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\ProductListing\ProductDraftInterface;
use BitBag\SyliusMultiVendorMarketplacePlugin\Entity\ProductListing\ProductListingInterface;

interface ProductDraftRepositoryInterface
{
    public function save(ProductDraftInterface $productDraft): void;

    public function findLatestDraft(ProductListingInterface $productListing): ?ProductDraftInterface;

    /**
     * @return mixed
     */
    public function find(
        int $id,
        ?int $lockMode = null,
        ?int $lockVersion = null
    );
}
