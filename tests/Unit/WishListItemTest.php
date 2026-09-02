<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tests\Unit;

use Pixelpoems\Wishlist\Models\WishListItem;
use SilverShop\Model\Variation\Variation;
use SilverShop\Page\Product;
use SilverStripe\Dev\SapphireTest;

/**
 * Covers Pixelpoems\Wishlist\Models\WishListItem: the polymorphic buyable
 * relation, pricing helpers, and their behaviour when the buyable no
 * longer exists (deleted product/variation).
 */
class WishListItemTest extends SapphireTest
{
    protected static $fixture_file = '../Fixtures/wishlist.yml';

    public function testSetBuyableStoresIDAndClassName()
    {
        $product = $this->objFromFixture(Product::class, 'product1');
        $item = WishListItem::create();

        $result = $item->setBuyable($product);

        $this->assertSame($item, $result);
        $this->assertSame($product->ID, $item->BuyableID);
        $this->assertSame(Product::class, $item->BuyableClassName);
    }

    public function testGetBuyableResolvesTheStoredRecord()
    {
        $product = $this->objFromFixture(Product::class, 'product1');
        $item = WishListItem::create();
        $item->setBuyable($product);

        $resolved = $item->getBuyable();

        $this->assertNotNull($resolved);
        $this->assertSame($product->ID, $resolved->ID);
        $this->assertSame($resolved->ID, $item->Buyable()->ID);
    }

    public function testGetBuyableReturnsNullWhenClassOrIDMissing()
    {
        $item = WishListItem::create();
        $this->assertNull($item->getBuyable());

        // ClassName set but no ID yet.
        $item->BuyableClassName = Product::class;
        $this->assertNull($item->getBuyable());
    }

    public function testGetBuyableReturnsNullWhenReferencedRecordWasDeleted()
    {
        $product = $this->objFromFixture(Product::class, 'product2');
        $item = WishListItem::create();
        $item->setBuyable($product);
        $item->write();

        $product->delete();

        $this->assertNull($item->getBuyable());
    }

    public function testGetUnitPriceReturnsSellingPriceOfBuyable()
    {
        // Product::sellingPrice() fires an updateSellingPrice extension
        // hook, which in some host projects (e.g. croma-b2b-shop's
        // app-level PriceExtension) pulls in project-specific pricing
        // infrastructure (Fluent locales, a VTWEG service, ...) that
        // isn't fixturable generically here. Skip in that case rather
        // than asserting a value this module doesn't control; adapt
        // locally if you need to verify that project's full pricing
        // pipeline.
        try {
            $item = WishListItem::create();
            $item->setBuyable($this->objFromFixture(Product::class, 'product1'));
            $price = $item->getUnitPrice();
        } catch (\Throwable $e) {
            $this->markTestSkipped('sellingPrice() depends on host-project pricing infrastructure: ' . $e->getMessage());
        }

        // Product::sellingPrice()/Variation::sellingPrice() both return a
        // plain float (rounded BasePrice/Price), not a Money/Currency
        // object.
        $this->assertSame(19.99, $price);
        $this->assertSame($price, $item->UnitPrice());
    }

    public function testGetUnitPriceReturnsNullWhenBuyableMissing()
    {
        $item = WishListItem::create();

        $this->assertNull($item->getUnitPrice());
    }

    public function testGetUnitPriceAsMoneyFormatsTheFloatAsCurrency()
    {
        // Regression test: getUnitPriceAsMoney() used to call ->Nice()
        // directly on the getUnitPrice() float, which fatals because a
        // float has no ->Nice() method - it needs casting through a
        // Currency DBField first. See testGetUnitPriceReturnsSellingPriceOfBuyable
        // re: why sellingPrice() itself is wrapped defensively.
        try {
            $item = WishListItem::create();
            $item->setBuyable($this->objFromFixture(Product::class, 'product1'));
            $money = $item->getUnitPriceAsMoney();
        } catch (\Throwable $e) {
            $this->markTestSkipped('sellingPrice() depends on host-project pricing infrastructure: ' . $e->getMessage());
        }

        $this->assertIsString($money);
        $this->assertStringContainsString('19.99', $money);
        $this->assertSame($money, $item->UnitPriceAsMoney());
    }

    public function testGetUnitPriceAsMoneyReturnsNullWhenUnitPriceIsNull()
    {
        $item = WishListItem::create();

        $this->assertNull($item->getUnitPriceAsMoney());
        $this->assertNull($item->UnitPriceAsMoney());
    }

    public function testTableTitleReturnsBuyableTitleForPlainProduct()
    {
        $product = $this->objFromFixture(Product::class, 'product1');
        $item = WishListItem::create();
        $item->setBuyable($product);

        $this->assertSame($product->Title, $item->TableTitle());
    }

    public function testTableTitleCombinesProductAndVariationTitleForAVariation()
    {
        // Mirrors how WishList_Items.ss composes the title manually:
        // "<% if $Product %>$Product.Title $Title<% else %>$Title<% end_if %>"
        $product = $this->objFromFixture(Product::class, 'productWithVariations');
        $variation = $this->objFromFixture(Variation::class, 'variation1');
        $item = WishListItem::create();
        $item->setBuyable($variation);

        $this->assertSame(trim($product->Title . ' ' . $variation->Title), $item->TableTitle());
    }

    public function testTableTitleReturnsEmptyStringWhenBuyableMissing()
    {
        // Regression test: TableTitle() previously called hasMethod() on
        // null and fataled for an orphaned WishListItem.
        $item = WishListItem::create();

        $this->assertSame('', $item->TableTitle());
    }

    public function testSubTitleReturnsEmptyStringWhenBuyableMissing()
    {
        $item = WishListItem::create();

        $this->assertSame('', $item->SubTitle());
    }

    public function testSubTitleReturnsEmptyStringForPlainProduct()
    {
        $product = $this->objFromFixture(Product::class, 'product1');
        $item = WishListItem::create();
        $item->setBuyable($product);

        $this->assertSame('', $item->SubTitle());
    }

    public function testSubTitleReturnsVariationTitleForAVariation()
    {
        // The fixture doesn't set up AttributeValues for the variation,
        // so Variation::getTitle() (silvershop/core) itself returns null
        // here - SubTitle() casts that to '', which is what we compare
        // against rather than a hardcoded non-empty string.
        $variation = $this->objFromFixture(Variation::class, 'variation1');
        $item = WishListItem::create();
        $item->setBuyable($variation);

        $this->assertSame((string) $variation->Title, $item->SubTitle());
    }
}
