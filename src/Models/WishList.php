<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Models;

use Pixelpoems\Wishlist\Pages\WishListPage;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
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
        // Random token for guest (not-logged-in) wishlists, mirrored into the
        // session. NOT the PHP session id - that gets regenerated on login
        // (see MemberExtension), so it can't be used as a lookup key.
        'SessionKey' => 'Varchar(64)',
    ];

    private static array $indexes = [
        'SessionKey' => true,
    ];

    private static array $has_one = [
        'Owner' => Member::class,
    ];

    private static array $has_many = [
        'Items' => WishListItem::class,
    ];

    private const SESSION_KEY_NAME = 'Wishlist.GuestToken';

    protected static $current;

    public static function current(): ?WishList
    {
        $currentUser = Security::getCurrentUser();

        if (!$currentUser) {
            if (!WishListPage::config()->get('enable_wishlist_without_login')) {
                // No User logged in and wishlist without login is disabled, return null
                return null;
            }

            if (!isset(self::$current) || !self::$current) {
                $list = self::findSessionList();

                if (!$list || !$list->exists()) {
                    self::$current = WishList::create([
                        'Title'      => 'Wish List',
                        'SessionKey' => self::getOrCreateSessionKey(),
                    ]);
                } else {
                    self::$current = $list;
                }
            }

            return self::$current;
        }

        if (!isset(self::$current) || !self::$current) {
            $list = WishList::get()->filter(['OwnerID' => $currentUser->ID])
                ->sort(['LastEdited' => 'DESC'])
                ->first();

            if (!$list || !$list->exists()) {
                self::$current = WishList::create([
                    'Title'     => 'Wish List',
                    'OwnerID'   => $currentUser->ID,
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

    /**
     * Look up the guest wishlist for the token stored in the current session,
     * without creating one if none exists yet.
     */
    public static function findSessionList(): ?WishList
    {
        $token = self::getSessionKey();

        if (!$token) {
            return null;
        }

        return WishList::get()->filter(['SessionKey' => $token])->first();
    }

    /**
     * Merge a guest (session-based) wishlist into a member's wishlist after
     * login, then discard the guest list and its session token.
     */
    public static function mergeSessionListIntoMember(Member $member): void
    {
        $sessionList = self::findSessionList();

        if (!$sessionList || !$sessionList->exists()) {
            self::clearSessionKey();
            return;
        }

        $memberList = WishList::get()->filter(['OwnerID' => $member->ID])
            ->sort(['LastEdited' => 'DESC'])
            ->first();

        if (!$memberList || !$memberList->exists()) {
            $memberList = WishList::create(['Title' => 'Wish List', 'OwnerID' => $member->ID]);
            $memberList->write();
        }

        foreach ($sessionList->Items() as $item) {
            $buyable = $item->getBuyable();

            if ($buyable) {
                $memberList->addBuyable($buyable);
            }
        }

        $sessionList->removeAllBuyables();
        $sessionList->delete();
        self::clearSessionKey();

        self::$current = $memberList;
    }

    private static function getSession(): ?Session
    {
        return Controller::curr()?->getRequest()?->getSession();
    }

    private static function getSessionKey(): ?string
    {
        return self::getSession()?->get(self::SESSION_KEY_NAME);
    }

    private static function getOrCreateSessionKey(): ?string
    {
        $session = self::getSession();

        if (!$session) {
            return null;
        }

        $token = $session->get(self::SESSION_KEY_NAME);

        if (!$token) {
            $token = bin2hex(random_bytes(16));
            $session->set(self::SESSION_KEY_NAME, $token);
        }

        return $token;
    }

    private static function clearSessionKey(): void
    {
        self::getSession()?->clear(self::SESSION_KEY_NAME);
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->OwnerID && ($currentUser = Security::getCurrentUser())) {
            $this->OwnerID = $currentUser->ID;
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

        $existing = WishListItem::get()->filter([
            'WishListID' => $this->ID,
            'BuyableClassName' => $item->ClassName,
            'BuyableID' => $item->ID,
        ])->first();

        if (!$existing) {
            return false;
        }

        $existing->delete();
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
