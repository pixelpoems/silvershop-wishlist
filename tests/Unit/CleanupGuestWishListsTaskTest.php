<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tests\Unit;

use Pixelpoems\Wishlist\Models\WishList;
use Pixelpoems\Wishlist\Models\WishListItem;
use Pixelpoems\Wishlist\Tasks\CleanupGuestWishListsTask;
use SilverShop\Page\Product;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Covers Pixelpoems\Wishlist\Tasks\CleanupGuestWishListsTask: only unowned
 * (guest) wish lists past the retention window get deleted - owned lists
 * and recent guest lists must survive.
 */
class CleanupGuestWishListsTaskTest extends SapphireTest
{
    protected static $fixture_file = '../Fixtures/wishlist.yml';

    protected function setUp(): void
    {
        parent::setUp();
        // Guest lists in these tests must persist with OwnerID = 0 -
        // WishList::onBeforeWrite() auto-assigns OwnerID from the
        // currently logged-in member otherwise.
        $this->logOut();
    }

    /**
     * DataObject::write() always stamps LastEdited with the current time,
     * so backdating a fixture/record to simulate an old, abandoned guest
     * list needs a direct SQL update instead.
     */
    private function backdateLastEdited(WishList $list, string $when): void
    {
        SQLUpdate::create('"WishList"', ['"LastEdited"' => $when], ['"ID"' => $list->ID])->execute();
    }

    private function runTask(): string
    {
        $task = new CleanupGuestWishListsTask();
        $buffer = new BufferedOutput();
        $output = new PolyOutput(PolyOutput::FORMAT_ANSI, wrappedOutput: $buffer);

        $task->run(new ArrayInput([]), $output);

        return $buffer->fetch();
    }

    public function testDeletesOnlyStaleUnownedListsPastRetention()
    {
        $stale = WishList::create(['Title' => 'Wish List', 'SessionKey' => 'stale-token']);
        $stale->write();
        $this->backdateLastEdited($stale, date('Y-m-d H:i:s', strtotime('-40 days')));

        $recent = WishList::create(['Title' => 'Wish List', 'SessionKey' => 'recent-token']);
        $recent->write();

        $owned = $this->objFromFixture(WishList::class, 'list1'); // has an Owner, must survive
        $this->backdateLastEdited($owned, date('Y-m-d H:i:s', strtotime('-40 days')));

        $staleID = $stale->ID;

        $this->runTask();

        $this->assertNull(WishList::get()->byID($staleID));
        $this->assertNotNull(WishList::get()->byID($recent->ID));
        $this->assertNotNull(WishList::get()->byID($owned->ID));
    }

    public function testRespectsConfiguredRetentionPeriod()
    {
        Config::modify()->set(CleanupGuestWishListsTask::class, 'retention_days', 5);

        $list = WishList::create(['Title' => 'Wish List', 'SessionKey' => 'six-days-old']);
        $list->write();
        $this->backdateLastEdited($list, date('Y-m-d H:i:s', strtotime('-6 days')));

        $this->runTask();

        $this->assertNull(WishList::get()->byID($list->ID));
    }

    public function testDeletesOrphanedItemsAlongWithTheGuestList()
    {
        $stale = WishList::create(['Title' => 'Wish List', 'SessionKey' => 'stale-with-items']);
        $stale->write();
        $stale->addBuyable($this->objFromFixture(Product::class, 'product2'));
        $this->backdateLastEdited($stale, date('Y-m-d H:i:s', strtotime('-40 days')));
        $staleID = $stale->ID;

        $this->runTask();

        $this->assertNull(WishList::get()->byID($staleID));
        $this->assertSame(0, WishListItem::get()->filter('WishListID', $staleID)->count());
    }

    public function testOutputReportsHowManyListsWereDeleted()
    {
        $stale = WishList::create(['Title' => 'Wish List', 'SessionKey' => 'stale-token']);
        $stale->write();
        $this->backdateLastEdited($stale, date('Y-m-d H:i:s', strtotime('-40 days')));

        $output = $this->runTask();

        $this->assertStringContainsString('Deleted 1 abandoned guest wish list', $output);
    }
}