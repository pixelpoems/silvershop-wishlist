<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tests\Unit;

use Pixelpoems\Wishlist\Pages\WishListPage;
use SilverShop\Page\Product;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\SecurityToken;

/**
 * Covers Pixelpoems\Wishlist\Pages\WishListPage: the auto-created singleton
 * page and its add/remove link builders.
 */
class WishListPageTest extends SapphireTest
{
    private function deleteAllWishListPages(): void
    {
        foreach (WishListPage::get() as $page) {
            $page->delete();
        }
    }

    public function testRequireDefaultRecordsCreatesPageWhenNoneExists()
    {
        $this->deleteAllWishListPages();
        // Don't rely on the class-level default - the host project may
        // enable this in its own project config (e.g. mysite.yml).
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', false);

        (new WishListPage())->requireDefaultRecords();

        $this->assertSame(1, WishListPage::get()->count());

        $page = WishListPage::get()->first();
        $this->assertFalse((bool) $page->ShowInMenus);
        $this->assertFalse((bool) $page->ShowInSearch);
        $this->assertSame('LoggedInUsers', $page->CanViewType);
    }

    public function testRequireDefaultRecordsIsIdempotent()
    {
        $this->deleteAllWishListPages();

        (new WishListPage())->requireDefaultRecords();
        $countAfterFirstRun = WishListPage::get()->count();

        (new WishListPage())->requireDefaultRecords();

        $this->assertSame($countAfterFirstRun, WishListPage::get()->count());
    }

    public function testRequireDefaultRecordsRespectsAutoCreatePageConfigOff()
    {
        $this->deleteAllWishListPages();
        Config::modify()->set(WishListPage::class, 'auto_create_page', false);

        (new WishListPage())->requireDefaultRecords();

        $this->assertSame(0, WishListPage::get()->count());
    }

    public function testAddItemLinkReturnsHashWhenNoPageExists()
    {
        $this->deleteAllWishListPages();

        $this->assertSame('#', WishListPage::add_item_link(1, Product::class));
    }

    public function testAddItemLinkBuildsUrlWithSanitisedClassNameAndSecurityToken()
    {
        WishListPage::create(['Title' => 'Wish List'])->write();

        $url = WishListPage::add_item_link(42, Product::class);

        $sanitisedClass = str_replace('\\', '-', Product::class);
        $this->assertStringContainsString("add/42/{$sanitisedClass}", $url);
        $this->assertStringContainsString(SecurityToken::inst()->getName() . '=', $url);
    }

    public function testRemoveItemLinkBuildsUrlWithSanitisedClassNameAndSecurityToken()
    {
        WishListPage::create(['Title' => 'Wish List'])->write();

        $url = WishListPage::remove_item_link(42, Product::class);

        $sanitisedClass = str_replace('\\', '-', Product::class);
        $this->assertStringContainsString("remove/42/{$sanitisedClass}", $url);
        $this->assertStringContainsString(SecurityToken::inst()->getName() . '=', $url);
    }

    public function testRequireDefaultRecordsSetsCanViewTypeToAnyoneWhenGuestWishlistEnabled()
    {
        $this->deleteAllWishListPages();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);

        (new WishListPage())->requireDefaultRecords();

        $this->assertSame('Anyone', WishListPage::get()->first()->CanViewType);
    }

    public function testCanViewIgnoresCanViewTypeWhenGuestWishlistEnabled()
    {
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $page = WishListPage::create(['Title' => 'Wish List', 'CanViewType' => 'LoggedInUsers']);
        $page->write();

        $this->assertTrue($page->canView(null));
    }

    public function testCanViewFallsBackToCanViewTypeWhenGuestWishlistDisabled()
    {
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', false);
        $page = WishListPage::create(['Title' => 'Wish List', 'CanViewType' => 'LoggedInUsers']);
        $page->write();

        $this->assertFalse($page->canView(null));
    }
}
