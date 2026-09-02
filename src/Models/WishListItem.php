<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

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
        if ($price === null) {
            return null;
        }

        // sellingPrice() (called via getUnitPrice()) returns a plain
        // float, not a Money/Currency object, so it has no ->Nice() of
        // its own - cast it through DBCurrency to get one.
        return DBField::create_field('Currency', $price)->Nice();
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

        // NOTE: buyable->Item() (both Product and Variation implement it
        // via the Buyable interface) returns a shopping-cart OrderItem,
        // not something describing the buyable itself - its TableTitle()
        // is just the generic OrderItem singular name (e.g. "Item"), not
        // the product's name. Build the display title directly instead,
        // mirroring how WishList_Items.ss composes it manually.
        $product = $buyable->hasMethod('Product') ? $buyable->Product() : null;
        if ($product && $product->exists()) {
            return trim($product->Title . ' ' . $buyable->Title);
        }

        return $buyable->Title;
    }

    public function SubTitle()
    {
        $buyable = $this->getBuyable();
        if (!$buyable) {
            return '';
        }

        // For a Variation, its own Title is the attribute description
        // (e.g. "Colour: Red, Size: L") - a plain Product has no
        // meaningful subtitle.
        $product = $buyable->hasMethod('Product') ? $buyable->Product() : null;
        return ($product && $product->exists()) ? (string) $buyable->Title : '';
    }
}
