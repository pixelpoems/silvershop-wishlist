<div id="WishListItems">
    <% if $CurrentList %>
        <% with $CurrentList %>
            <% if $Items.Count %>
                <table id="InformationTable" class="editable infotable wishlist__table">
                    <colgroup>
                        <col class="image"/>
                        <col class="product title"/>
                        <col class="addtocart"/>
                        <col class="remove"/>
                    </colgroup>
                    <tbody>
                    <% loop $Items %>
                        <tr class="<% if $Buyable.completeSoldOut || $Buyable.Product.completeSoldOut %>wishlist--item-soldout<% end_if %>">
                            <% with $Buyable %>
                                <td class="image">
                                    <% if $Image.ContentImage %>
                                        <img class="silvershop-product__image" <% include SilverShop\Includes\SchemaOrg\ProductImage %> src="$Image.ContentImage.URL" alt="<%t SilverShop\Page\Product.ImageAltText "{Title} image" Title=$Title %>" />
                                    <% else %>
                                        <div class="silvershop-product__no-image"><%t SilverShop\Page\Product.NoImage "no image" %></div>
                                    <% end_if %>

                                </td>
                                <td class="title">
                                    <%-- Product is set if current Buyable is a Variant --%>
                                    <a href="<% if $Variation %>$Link$VariationURLDecorator($ID)<% else %>$Link<% end_if %>">
                                       <%-- Product is set if current Buyable is a Variant --%>

                                       <% if $Product %>$Product.Title $Title<% else %>$Title<% end_if %>
                                   </a>
                                </td>
                                <td class="unitprice">$Price.Nice</td>
                                <td class="cartlink">
                                    <div class="add">
                                        <% if $canPurchase %>
                                            <div class="silvershop-product-card__add">
                                                <a class="silvershop-product-card__add-link" href="$addLink" title="<%t SilverShop\Page\Product.AddToCartTitle "Add &quot;{Title}&quot; to your cart" Title=$Title %>">
                                                    <%t SilverShop\Page\Product.AddToCart "Add to Cart" %>
                                                    <% if $IsInCart %>
                                                        ($Item.Quantity)
                                                    <% end_if %>
                                                </a>
                                            </div>
                                        <% else %>
                                            <div title="<%t Pixelpoems\Wishlist\Models\WishList.NOTPURCHASEABLE 'This item is not available for purchase.' %>">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L543.9 510.2L543.9 319.9C570.7 318 591.9 295.6 591.9 268.3C591.9 260.3 590 252.3 586.4 245.1L529.7 131.4C518.8 109.7 496.7 96 472.4 96L167.6 96C156.9 96 146.6 98.7 137.4 103.6L73 39.1zM353.8 320L480 320L480 446.2L353.8 320zM384 485.8L320 421.8L320 432C320 440.8 312.8 448 304 448L176 448C167.2 448 160 440.8 160 432L160 320L218.2 320L83.4 185.2L53.5 245.1C49.9 252.3 48 260.2 48 268.3C48 295.6 69.2 318 96 319.9L96 496C96 522.5 117.5 544 144 544L336 544C362.5 544 384 522.5 384 496L384 485.8z"/></svg>
                                            </div>
                                        <% end_if %>
                                    </div>
                                </td>

                            <% end_with %>
                            <td class="right remove">
                                <a class="ajax btn btn__icon" href="$Buyable.WishListRemoveLink" title="<%t Pixelpoems\Wishlist\Models\WishList.REMOVELINK "Remove from Wish List" %>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z"/></svg>
                                    $Top.WishListAjaxIndicator
                                </a>
                            </td>
                        </tr>
                    <% end_loop %>
                    </tbody>
                </table>
            <% else %>
                <p class="noItems"><%t Pixelpoems\Wishlist\Models\WishList.NOITEMS 'Your wish list is empty.' %></p>
            <% end_if %>
        <% end_with %>
    <% else %>
        <p class="noItems"><%t Pixelpoems\Wishlist\Models\WishList.NOITEMS 'Your wish list is empty.' %></p>
    <% end_if %>
</div>
