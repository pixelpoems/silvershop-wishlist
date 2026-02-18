<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Models;

use SilverStripe\ORM\DataObject;

class WishListItem extends DataObject
{
    private static string $table_name = 'WishList_Item';

    private static string $singular_name = 'Wish List Item';

    private static string $plural_name = 'Wish List Items';

    private static array $db = [
        'BuyableID' => 'Int',
        'BuyableClassName' => 'Varchar(60)',
    ];

    private static array $indexes = [
        "BuyableID" => true,
        "BuyableClassName" => true
    ];

    private static array $has_one = [
        'WishList' => WishList::class,
    ];

    private static array $casting = [
        'UnitPrice' => 'Currency',
    ];

    public function setBuyable($item): static
    {
        $this->BuyableID = $item->ID;
        $this->BuyableClassName = $item->ClassName;
        return $this;
    }

    public function getBuyable()
    {
        if (!$this->BuyableClassName || !$this->BuyableID) {
            return null;
        }

        return DataObject::get($this->BuyableClassName)->byID($this->BuyableID);
    }

    public function Buyable()
    {
        return $this->getBuyable();
    }

    public function getUnitPrice()
    {
        $product = $this->getBuyable();
        if ($product && $product->exists()) {
            return $product->sellingPrice();
        }

        return null;
    }

    public function UnitPrice()
    {
        return $this->getUnitPrice();
    }

    public function getUnitPriceAsMoney()
    {
        $price = $this->UnitPrice();
        return $price ? $price->Nice() : null;
    }

    public function UnitPriceAsMoney()
    {
        return $this->getUnitPriceAsMoney();
    }

    public function TableTitle()
    {
        $buyable = $this->getBuyable();
        if (!$buyable) {
            return '';
        }

        $item = $buyable->hasMethod('Item') ? $buyable->Item() : null;
        return $item ? $item->TableTitle() : $buyable->Title;
    }

    public function SubTitle()
    {
        $buyable = $this->getBuyable();
        if (!$buyable) {
            return '';
        }

        return $buyable->hasMethod('Item') ? $buyable->Item()->SubTitle() : '';
    }
}
