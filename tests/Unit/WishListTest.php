<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tests\Unit;

use Pixelpoems\Wishlist\Models\WishList;
use ReflectionProperty;
use SilverShop\Page\Product;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

/**
 * Covers Pixelpoems\Wishlist\Models\WishList: current-list resolution,
 * ownership, and the buyable add/remove/dedupe logic.
 */
class WishListTest extends SapphireTest
{
    protected static $fixture_file = '../Fixtures/wishlist.yml';

    protected function setUp(): void
    {
        parent::setUp();
        // WishList::current() caches its result in a plain static property
        // that SapphireTest does not reset between tests, so clear it here
        // to keep tests isolated from each other.
        $property = new ReflectionProperty(WishList::class, 'current');
        $property->setValue(null, null);
    }

    public function testCurrentReturnsNullWhenNoMemberIsLoggedIn()
    {
        $this->logOut();

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
}
