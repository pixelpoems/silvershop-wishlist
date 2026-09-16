<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Extensions;

use Pixelpoems\Wishlist\Models\WishList;
use SilverStripe\Core\Extension;

class MemberExtension extends Extension
{
    /**
     * Merge any guest wishlist built up in the current session into the
     * member's own wishlist once they log in.
     */
    public function onAfterMemberLoggedIn(): void
    {
        WishList::mergeSessionListIntoMember($this->getOwner());
    }
}