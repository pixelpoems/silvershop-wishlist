<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Extensions;

use SilverStripe\ORM\DataObject;
use Pixelpoems\Wishlist\Models\WishList;
use Pixelpoems\Wishlist\Pages\WishListPage;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

class PageControllerExtension extends Extension
{
    public function onAfterInit(): void
    {
        // This is a good place to include any JS or CSS needed for the wishlist functionality
        Requirements::javascript('_resources/vendor/pixelpoems/silvershop-wishlist/client/dist/javascript/wishlist.min.js');
        Requirements::css('_resources/vendor/pixelpoems/silvershop-wishlist/client/dist/css/wishlist.min.css');
    }

    public function getWishListPageLink()
    {
        if (!Security::getCurrentUser()) {
            return Security::login_url();
        }

        if (($WishListPage = WishListPage::inst()) instanceof DataObject) {
            return $WishListPage->AbsoluteLink();
        }

        return null;
    }

    public function getWishListItemCount()
    {
        if (($WishList = WishList::current()) instanceof WishList) {
            return $WishList->getBuyableCount();
        }

        return null;
    }
}
