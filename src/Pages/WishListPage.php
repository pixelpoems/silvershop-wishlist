<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Pages;

use SilverStripe\ORM\DataObject;
use Page;
use Pixelpoems\Wishlist\Controllers\WishListPageController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\SecurityToken;

class WishListPage extends Page
{

    private static string $table_name = 'WishListPage';

    private static string $singular_name = 'Wish List Page';

    private static string $plural_name = 'Wish List Pages';

    private static string $cms_icon_class = 'font-icon-circle-star';

    /**
     * @var bool - allows you to override requireDefaultRecords if needed via config
     */
    private static bool $auto_create_page = true;

    public function getControllerName(): string
    {
        return WishListPageController::class;
    }

    public static function inst(): ?DataObject
    {
        return self::get()->first();
    }

    /**
     * @param $id
     * @param $className
     */
    public static function add_item_link($id, $className): string
    {
        if (!self::inst() instanceof DataObject) {
            return '#';
        }

        $sanitisedClassname = str_replace('\\', '-', $className);

        $url = sprintf('%sadd/%d/%s', self::inst()->Link(), $id, $sanitisedClassname);
        return SecurityToken::inst()->addToUrl($url);
    }

    /**
     * @param $id
     * @param $className
     */
    public static function remove_item_link($id, $className): string
    {
        if (!self::inst() instanceof DataObject) {
            return '#';
        }

        $sanitisedClassname = str_replace('\\', '-', $className);

        $url = sprintf('%sremove/%d/%s', self::inst()->Link(), $id, $sanitisedClassname);
        return SecurityToken::inst()->addToUrl($url);
    }


    /**
     * Create the page if needed
     */
    public function requireDefaultRecords(): void
    {
        if (!self::inst() && Config::inst()->get(self::class, 'auto_create_page')) {
            $rec = WishListPage::create();
            $rec->Title = 'Wish List';
            $rec->ShowInSearch = false;
            $rec->ShowInMenus = false;
            $rec->CanViewType = 'LoggedInUsers';
            $rec->write();
            $rec->publishRecursive();
            $rec->flushCache();
        }
    }
}
