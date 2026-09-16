<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tests\Unit;

use Pixelpoems\Wishlist\Extensions\MemberExtension;
use Pixelpoems\Wishlist\Models\WishList;
use Pixelpoems\Wishlist\Pages\WishListPage;
use Pixelpoems\Wishlist\Tests\GuestSessionHelper;
use SilverShop\Page\Product;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

/**
 * Covers Pixelpoems\Wishlist\Extensions\MemberExtension: the guest-wishlist
 * merge fired via Member's real onAfterMemberLoggedIn extension point
 * (Member::afterMemberLoggedIn(), not the SapphireTest::logInAs() shortcut,
 * which bypasses it entirely).
 */
class MemberExtensionTest extends SapphireTest
{
    use GuestSessionHelper;

    protected static $fixture_file = '../Fixtures/wishlist.yml';

    protected static $required_extensions = [
        Member::class => [MemberExtension::class],
    ];

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

    public function testMemberLoginMergesGuestWishlistViaTheRealExtensionHook()
    {
        $this->logOut();
        Config::modify()->set(WishListPage::class, 'enable_wishlist_without_login', true);
        $this->startGuestSession();

        $product = $this->objFromFixture(Product::class, 'product2');
        WishList::current()->addBuyable($product);

        $member = $this->objFromFixture(Member::class, 'member2');
        // Fires the real onAfterMemberLoggedIn extension point - the same
        // path SilverStripe's login handlers call on a real login, unlike
        // logInAs() which only calls Security::setCurrentUser().
        $member->afterMemberLoggedIn();

        $memberList = WishList::get_for_user($member)->first();
        $this->assertNotNull($memberList);
        $this->assertTrue($memberList->hasBuyable($product));
        $this->assertNull(WishList::findSessionList());
    }
}