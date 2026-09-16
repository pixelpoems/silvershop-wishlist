<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tests\Unit;

use Pixelpoems\Wishlist\Extensions\CanAddToWishListExtension;
use Pixelpoems\Wishlist\Models\WishList;
use Pixelpoems\Wishlist\Pages\WishListPage;
use Pixelpoems\Wishlist\Tests\GuestSessionHelper;
use SilverShop\Model\Variation\Variation;
use SilverShop\Page\Product;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Covers Pixelpoems\Wishlist\Extensions\CanAddToWishListExtension, applied
 * to SilverShop\Page\Product and SilverShop\Model\Variation\Variation.
 */
class CanAddToWishListExtensionTest extends SapphireTest
{
    use GuestSessionHelper;

    protected static $fixture_file = '../Fixtures/wishlist.yml';

    protected static $required_extensions = [
        Product::class => [CanAddToWishListExtension::class],
        Variation::class => [CanAddToWishListExtension::class],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCurrentListCache();

        // WishListAddLink()/WishListRemoveLink() return '#' unless a
        // WishListPage exists.
        if (!WishListPage::get()->exists()) {
            WishListPage::create(['Title' => 'Wish List'])->write();
        }
    }

    protected function tearDown(): void
    {
        $this->stopGuestSession();
        parent::tearDown();
    }

    public function testWishListAddLinkForPlainProduct()
    {
        $product = $this->objFromFixture(Product::class, 'product2');

        $sanitisedClass = str_replace('\\', '-', Product::class);
        $this->assertStringContainsString(
            "add/{$product->ID}/{$sanitisedClass}",
            $product->WishListAddLink()
        );
    }

    public function testWishListAddLinkForProductWithVariationsUsesFirstVariation()
    {
        $product = $this->objFromFixture(Product::class, 'productWithVariations');
        $variation = $this->objFromFixture(Variation::class, 'variation1');

        $sanitisedClass = str_replace('\\', '-', Variation::class);
        $this->assertStringContainsString(
            "add/{$variation->ID}/{$sanitisedClass}",
            $product->WishListAddLink()
        );
    }

    public function testWishListRemoveLinkMirrorsAddLinkBehaviour()
    {
        $product = $this->objFromFixture(Product::class, 'product2');

        $sanitisedClass = str_replace('\\', '-', Product::class);
        $this->assertStringContainsString(
            "remove/{$product->ID}/{$sanitisedClass}",
            $product->WishListRemoveLink()
        );
    }

    public function testIsInWishListFalseWhenNobodyLoggedIn()
    {
        $this->logOut();
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertFalse($product->IsInWishList());
    }

    public function testIsInWishListTrueWhenItemOnCurrentMembersList()
    {
        $this->logInAs('member1');
        // product1 is on list1 via fixture item1.
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertTrue($product->IsInWishList());
    }

    public function testIsInWishListForProductDelegatesToFirstVariation()
    {
        $this->logInAs('member1');
        $product = $this->objFromFixture(Product::class, 'productWithVariations');
        $variation = $this->objFromFixture(Variation::class, 'variation1');
        $list = $this->objFromFixture(WishList::class, 'list1');

        $this->assertFalse($product->IsInWishList());

        $list->addBuyable($variation);

        $this->assertTrue($product->IsInWishList());
    }

    public function testWishListItemsReturnsNullWhenNobodyLoggedIn()
    {
        $this->logOut();
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertNull($product->WishListItems());
    }

    public function testWishListItemsReturnsNullWhenMemberHasNoWishList()
    {
        $this->logInAs('member2');
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertNull($product->WishListItems());
    }

    public function testWishListItemsFiltersByBuyableIDAndClassName()
    {
        $this->logInAs('member1');
        $product1 = $this->objFromFixture(Product::class, 'product1');
        $product2 = $this->objFromFixture(Product::class, 'product2');

        $items = $product1->WishListItems();

        $this->assertNotNull($items);
        $this->assertSame(1, $items->count());
        $this->assertSame($product1->ID, $items->first()->BuyableID);
        $this->assertSame(0, $product2->WishListItems()->count());
    }

    public function testWishListItemsReturnsNullForGuestWhenGuestWishlistDisabled()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', false);
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertNull($product->WishListItems());
    }

    public function testWishListItemsReturnsGuestItemsWhenGuestWishlistEnabled()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $product = $this->objFromFixture(Product::class, 'product1');
        WishList::current()->addBuyable($product);

        $items = $product->WishListItems();

        $this->assertNotNull($items);
        $this->assertSame(1, $items->count());
        $this->assertTrue($product->IsInWishList());
    }

    public function testWishlistAvailableTrueWhenMemberIsLoggedIn()
    {
        $this->logInAs('member1');
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertTrue($product->WishlistAvailable());
    }

    public function testWishlistAvailableFalseForGuestWhenGuestWishlistDisabled()
    {
        $this->logOut();
        // Don't rely on the class-level default - the host project may
        // enable this in its own project config (e.g. mysite.yml).
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', false);
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertFalse($product->WishlistAvailable());
    }

    public function testWishlistAvailableTrueForGuestWhenGuestWishlistEnabled()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $product = $this->objFromFixture(Product::class, 'product1');

        $this->assertTrue($product->WishlistAvailable());
    }
}
