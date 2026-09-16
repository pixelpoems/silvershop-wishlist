<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tasks;

use Pixelpoems\Wishlist\Models\WishList;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Deletes guest (not-logged-in) wishlists that were never merged into a
 * member's account and haven't been touched in a while - their session
 * has long since expired, so they're unreachable dead rows.
 *
 * Schedule this via a real cron entry, e.g. daily:
 * php vendor/silverstripe/framework/cli-script.php tasks:CleanupGuestWishListsTask
 */
class CleanupGuestWishListsTask extends BuildTask
{
    protected static string $commandName = 'CleanupGuestWishListsTask';

    protected string $title = 'Cleanup Guest Wish Lists Task';

    protected static string $description = 'Removes abandoned guest (not-logged-in) wish lists older than the configured retention period';

    private static int $retention_days = 30;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . static::config()->get('retention_days') . ' days'));

        $orphaned = WishList::get()->filter([
            'OwnerID' => 0,
            'LastEdited:LessThan' => $cutoff,
        ]);

        $count = 0;

        foreach ($orphaned as $list) {
            $list->removeAllBuyables();
            $list->delete();
            ++$count;
        }

        $output->writeln("Deleted {$count} abandoned guest wish list(s) older than {$cutoff}.");

        return Command::SUCCESS;
    }
}