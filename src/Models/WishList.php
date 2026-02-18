<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Models;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class WishList extends DataObject
{
    private static string $table_name = 'WishList';

    private static string $singular_name = 'Wish List';

    private static string $plural_name = 'Wish Lists';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $has_one = [
        'Owner' => Member::class,
    ];

    private static array $has_many = [
        'Items' => WishListItem::class,
    ];

    protected static $current;

    public static function current(): ?WishList
    {
        if (!Security::getCurrentUser()) {
            return null;
        }

        if (!isset(self::$current) || !self::$current) {
            $list = WishList::get()->filter(['OwnerID' => Security::getCurrentUser()->ID])
                ->sort(['LastEdited' => 'DESC'])
                ->first();

            if (!$list || !$list->exists()) {
                self::$current = WishList::create([
                    'Title'     => 'Wish List',
                    'OwnerID'   => Security::getCurrentUser()->ID,
                ]);
            } else {
                self::$current = $list;
            }
        }

        return self::$current;
    }

//    public static function set_current($list)
//    {
//        if ($list) {
//            $list->write(false, false, true);
//        } // force LastEdited to change
//        self::$current = $list;
//    }

    public static function get_for_user(?Member $member = null): ?DataList
    {
        if (!$member instanceof Member) {
            $member = Security::getCurrentUser();
        }

        if (!$member) {
            return null;
        }

        return WishList::get()->filter(['OwnerID' => $member->ID]);
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->OwnerID) {
            $this->OwnerID = Security::getCurrentUser()->ID;
        }
    }

    public function getBuyableCount()
    {
        return $this->Items()->count();
    }

    public function hasBuyable($item): bool
    {
        if (!$this->ID) {
            return false;
        }

        $existing = WishListItem::get()->filter([
            'WishListID' => $this->ID,
            'BuyableClassName' => $item->ClassName,
            'BuyableID' => $item->ID,
        ]);

        return ($existing->count() > 0);
    }

    public function addBuyable($item): bool
    {
        if (!$this->ID) {
            $this->write();
        }

        if ($this->hasBuyable($item)) {
            return false;
        }

        $myItem = WishListItem::create();
        $myItem->setBuyable($item);
        $myItem->WishListID = $this->ID;
        $myItem->write();

        return true;
    }

    /**
     * This is also called within the updateAddToCart() method in the AddProductFormExtension
     * to remove the item from the wishlist if it's added to the cart.
     * @param $item
     */
    public function removeBuyable($item): bool
    {
        if (!$this->ID) {
            return false;
        }

        if (!$this->hasBuyable($item)) {
            return false;
        }

        $item->WishListItem()->delete();
        return true;
    }

    public function removeAllBuyables(): int
    {
        $items = $this->Items();
        $count = 0;

        foreach ($items as $item) {
            $item->delete();
            ++$count;
        }

        return $count;
    }

//    public function getSetCurrentLink()
//    {
//        $url = WishListPage::inst()->Link('set-current-list/' . $this->ID);
//        return SecurityToken::inst()->addToUrl($url);
//    }
}
