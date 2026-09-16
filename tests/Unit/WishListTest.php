<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tests\Unit;

use Pixelpoems\Wishlist\Models\WishList;
use Pixelpoems\Wishlist\Pages\WishListPage;
use Pixelpoems\Wishlist\Tests\GuestSessionHelper;
use SilverShop\Page\Product;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

/**
 * Covers Pixelpoems\Wishlist\Models\WishList: current-list resolution,
 * ownership, the buyable add/remove/dedupe logic, and the guest
 * (not-logged-in) session list plus its merge into a member on login.
 */
class WishListTest extends SapphireTest
{
    use GuestSessionHelper;

    protected static $fixture_file = '../Fixtures/wishlist.yml';

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCurrentListCache();
    }

    protected function tearDown(): void
    {
        $this->stopGuestSession();
        parent::tearDown();
    }

    public function testCurrentReturnsNullWhenNoMemberIsLoggedIn()
    {
        $this->logOut();
        // Don't rely on the class-level default - the host project may
        // enable this in its own project config (e.g. mysite.yml).
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', false);

        $this->assertNull(WishList::current());
    }

    public function testCurrentReturnsExistingListForLoggedInMember()
    {
        $this->logInAs('member1');
        $existing = $this->objFromFixture(WishList::class, 'list1');

        $current = WishList::current();

        $this->assertNotNull($current);
        $this->assertSame($existing->ID, $current->ID);
    }

    public function testCurrentCreatesAndPersistsListWhenMemberHasNone()
    {
        $member = $this->objFromFixture(Member::class, 'member2');
        $this->assertSame(0, WishList::get_for_user($member)->count());

        $this->logInAs($member);
        $current = WishList::current();

        $this->assertNotNull($current);
        $this->assertSame($member->ID, $current->OwnerID);
    }

    public function testGetForUserFiltersByGivenMember()
    {
        $member1 = $this->objFromFixture(Member::class, 'member1');
        $member2 = $this->objFromFixture(Member::class, 'member2');

        $lists = WishList::get_for_user($member1);

        $this->assertSame(1, $lists->count());
        $this->assertSame($member1->ID, $lists->first()->OwnerID);
        $this->assertSame(0, WishList::get_for_user($member2)->count());
    }

    public function testGetForUserFallsBackToCurrentlyLoggedInMember()
    {
        $this->logOut();
        $this->assertNull(WishList::get_for_user());

        $this->logInAs('member1');
        $this->assertSame(1, WishList::get_for_user()->count());
    }

    public function testAddBuyableAddsItemAndReturnsTrue()
    {
        $this->logInAs('member2');
        $list = WishList::current();
        $product = $this->objFromFixture(Product::class, 'product2');

        $result = $list->addBuyable($product);

        $this->assertTrue($result);
        $this->assertSame(1, $list->getBuyableCount());
    }

    public function testAddBuyableIsIdempotentForSameItem()
    {
        $this->logInAs('member2');
        $list = WishList::current();
        $product = $this->objFromFixture(Product::class, 'product2');

        $this->assertTrue($list->addBuyable($product));
        $this->assertFalse($list->addBuyable($product));
        $this->assertSame(1, $list->getBuyableCount());
    }

    public function testAddBuyableWritesUnsavedListBeforeAddingItem()
    {
        $list = WishList::create(['Title' => 'Wish List']);
        $this->assertSame(0, (int) $list->ID);

        $list->addBuyable($this->objFromFixture(Product::class, 'product1'));

        $this->assertGreaterThan(0, $list->ID);
    }

    public function testHasBuyableReturnsFalseForUnsavedList()
    {
        $list = WishList::create(['Title' => 'Wish List']);
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertFalse($list->hasBuyable($product));
    }

    public function testRemoveBuyableDeletesMatchingItemAndReturnsTrue()
    {
        $list = $this->objFromFixture(WishList::class, 'list1');
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertTrue($list->hasBuyable($product));

        $this->assertTrue($list->removeBuyable($product));
        $this->assertFalse($list->hasBuyable($product));
    }

    public function testRemoveBuyableReturnsFalseWhenItemNotOnList()
    {
        $list = $this->objFromFixture(WishList::class, 'list1');
        $product = $this->objFromFixture(Product::class, 'product2');

        $this->assertFalse($list->removeBuyable($product));
    }

    public function testRemoveAllBuyablesDeletesEveryItemAndReturnsCount()
    {
        $list = $this->objFromFixture(WishList::class, 'list1');
        $list->addBuyable($this->objFromFixture(Product::class, 'product2'));
        $this->assertSame(2, $list->getBuyableCount());

        $removed = $list->removeAllBuyables();

        $this->assertSame(2, $removed);
        $this->assertSame(0, $list->getBuyableCount());
    }

    public function testGetBuyableCountMatchesNumberOfItems()
    {
        $list = $this->objFromFixture(WishList::class, 'list1');

        $this->assertSame($list->Items()->count(), $list->getBuyableCount());
    }

    public function testOnBeforeWriteDefaultsOwnerIDToCurrentMember()
    {
        $this->logInAs('member2');
        $member = $this->objFromFixture(Member::class, 'member2');

        $list = WishList::create(['Title' => 'Wish List']);
        $list->write();

        $this->assertSame($member->ID, $list->OwnerID);
    }

    public function testCurrentCreatesGuestListWhenNoMemberAndGuestWishlistEnabled()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $current = WishList::current();

        $this->assertNotNull($current);
        $this->assertSame(0, (int) $current->OwnerID);
        $this->assertNotEmpty($current->SessionKey);
    }

    public function testCurrentReusesSameGuestListAcrossRequestsViaSessionToken()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $first = WishList::current();
        $first->addBuyable($this->objFromFixture(Product::class, 'product1')); // forces write()

        // Simulate a fresh request: clear the static cache but keep the
        // same (guest) session - and thus the same token - alive.
        $this->resetCurrentListCache();
        $second = WishList::current();

        $this->assertSame($first->ID, $second->ID);
    }

    public function testFindSessionListReturnsNullWithoutAnActiveGuestSession()
    {
        $this->logOut();

        $this->assertNull(WishList::findSessionList());
    }

    public function testFindSessionListReturnsNullForAnUnpersistedGuestList()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        // Generates and stores a session token, but the list itself is
        // never written (lazy write, same as for logged-in members) since
        // nothing was ever added to it.
        WishList::current();

        $this->assertNull(WishList::findSessionList());
    }

    public function testFindSessionListReturnsThePersistedGuestList()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $list = WishList::current();
        $list->addBuyable($this->objFromFixture(Product::class, 'product1'));

        $found = WishList::findSessionList();

        $this->assertNotNull($found);
        $this->assertSame($list->ID, $found->ID);
    }

    public function testMergeSessionListIntoMemberMovesItemsAndDiscardsGuestList()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $guestList = WishList::current();
        $product = $this->objFromFixture(Product::class, 'product2');
        $guestList->addBuyable($product);
        $guestListID = $guestList->ID;

        $member = $this->objFromFixture(Member::class, 'member2');
        WishList::mergeSessionListIntoMember($member);

        $memberList = WishList::get_for_user($member)->first();
        $this->assertNotNull($memberList);
        $this->assertTrue($memberList->hasBuyable($product));
        $this->assertNull(WishList::get()->byID($guestListID));
        $this->assertNull(WishList::findSessionList());
    }

    public function testMergeSessionListIntoMemberDedupesItemsAlreadyOnTheMembersList()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $guestList = WishList::current();
        // product1 is already on member1's list1 via fixture item1.
        $product = $this->objFromFixture(Product::class, 'product1');
        $guestList->addBuyable($product);

        $member = $this->objFromFixture(Member::class, 'member1');
        WishList::mergeSessionListIntoMember($member);

        $memberList = $this->objFromFixture(WishList::class, 'list1');
        $this->assertSame(1, $memberList->getBuyableCount());
    }

    public function testMergeSessionListIntoMemberCreatesAListWhenMemberHasNone()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $guestList = WishList::current();
        $guestList->addBuyable($this->objFromFixture(Product::class, 'product2'));

        $member = $this->objFromFixture(Member::class, 'member2');
        $this->assertSame(0, WishList::get_for_user($member)->count());

        WishList::mergeSessionListIntoMember($member);

        $this->assertSame(1, WishList::get_for_user($member)->count());
    }

    public function testMergeSessionListIntoMemberIsNoopWhenNoGuestListExists()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $member = $this->objFromFixture(Member::class, 'member2');
        WishList::mergeSessionListIntoMember($member);

        $this->assertSame(0, WishList::get_for_user($member)->count());
    }
}
