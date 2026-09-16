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
        if(WishListPage::config()->get('load_default_js')) {
            Requirements::javascript('_resources/vendor/pixelpoems/silvershop-wishlist/client/dist/javascript/wishlist.min.js');
        }

        if(WishListPage::config()->get('load_default_css')) {
            Requirements::css('_resources/vendor/pixelpoems/silvershop-wishlist/client/dist/css/wishlist.min.css');
        }
    }

    public function getWishListPageLink()
    {
        $wishlistLink = null;
        if(($wishListPage = WishListPage::inst()) instanceof DataObject) {
            $wishlistLink = $wishListPage->AbsoluteLink();
        }

        if(!$wishlistLink) return null;

        if (Security::getCurrentUser()) {
            return $wishlistLink;
        } else {
            if(WishListPage::config()->get('enable_wishlist_without_login')) {
                return $wishlistLink;
            }
            return Security::login_url();
        }
    }

    public function getWishListItemCount()
    {
        if (($WishList = WishList::current()) instanceof WishList) {
            return $WishList->getBuyableCount();
        }

        return null;
    }
}
