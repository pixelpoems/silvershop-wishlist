<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Extensions;

use Pixelpoems\Wishlist\Models\WishList;
use Pixelpoems\Wishlist\Models\WishListItem;
use Pixelpoems\Wishlist\Pages\WishListPage;
use SilverShop\Model\Variation\Variation;
use SilverShop\Page\Product;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

class CanAddToWishListExtension extends Extension
{
    public function WishListAddLink(): string
    {
        $ID = $this->getOwner()->ID;
        $className = $this->getOwner()->ClassName;

        // Update if class is product but product has variations
        if ($className === Product::class && $this->getOwner()->Variations()->count()) {
            // Use the variation class name instead of Product
            $className = Variation::class;
            $ID = $this->getOwner()->Variations()->first()->ID; // Use the first variation ID
        }

        return WishListPage::add_item_link($ID, $className);
    }

    public function WishListRemoveLink(): string
    {
        $ID = $this->getOwner()->ID;
        $className = $this->getOwner()->ClassName;

        // Update if class is product but product has variations
        if ($className === Product::class && $this->getOwner()->Variations()->count()) {
            // Use the variation class name instead of Product
            $className = Variation::class;
            $ID = $this->getOwner()->Variations()->first()->ID; // Use the first variation ID
        }

        return WishListPage::remove_item_link($ID, $className);
    }

    public function IsInWishList(): bool
    {
        $item = $this->WishListItem();

        if( $this->getOwner()->ClassName === Product::class && $firstVariation = $this->getOwner()?->Variations()?->first()) {
            return $firstVariation->IsInWishList();
        }

        return $item instanceof DataObject && $item->exists();
    }

    /**
     * IF this item is an any of the current member's wishlists,
     * returns the wishlist item record.
     *
     * NOTE: in order to be consistent with Product::OrderItem
     * this method returns a record NO MATTER WHAT, so you'll
     * have to check $rec->exists() to see if it's in the cart.
     */
    public function WishListItem(): ?DataObject
    {
        return $this->WishListItems() instanceof DataList ? $this->WishListItems()->first() : null;
    }

    /**
     * IF this item is an any of the current member's wishlists,
     * returns the wishlist item records.
     */
    public function WishListItems(): ?DataList
    {
        $currentMember = Security::getCurrentUser();
        if (!$currentMember) {
            return null;
        }

        $ID = $this->getOwner()->ID;
        $className = $this->getOwner()->ClassName;

        $wishlistIDs = WishList::get()->filter(['OwnerID' => $currentMember->ID])->column('ID');

        // ToDo: Update this if we ever have multiple wishlists
        if (!isset($wishlistIDs[0])) {
            return null;
        }

        return WishListItem::get()
            ->filter([
                'WishListID' => $wishlistIDs[0],
                'BuyableID' => $ID,
                'BuyableClassName' => $className,
            ]);
    }
}
