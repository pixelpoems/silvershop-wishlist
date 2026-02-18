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
                                    <% if $ClassName = 'SilverShop\Model\Variation\Variation' %>
                                        <% include SilverShop\Includes\ProductImage Locale=$Locale, Size=sm, Me=$Product.MainImage %>
                                    <% else %>
                                        <% include SilverShop\Includes\ProductImage Locale=$Locale, Size='sm', Me=$MainImage %>
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
                                <% if $canPurchase && not $isGift %>
                                    <div class="add">
                                        <%-- Product is set if current Buyable is a Variant --%>
                                        <% if $Product %>
                                            $VariationForm.setAttribute("id", $ID).setAttribute("data-proid", $Product.ID)
                                        <% else %>
                                            $Form
                                        <% end_if %>
                                    </div>
                                <% end_if %>

                                 <% if $completeSoldOut || $Product.completeSoldOut %>
                                     <%t SilverShop\Page\Product.completeSoldOutShort 'Sold out completely' %>
                                 <% end_if %>
                                </td>

                            <% end_with %>
                            <td class="right remove">
    <%--                             <a class="ajax" href="$Buyable.WishListRemoveLink" title="<%t WishList.REMOVELINK "Remove from Wish List" %>">
                                        <img class="hide-when-loading" src="shop/images/remove.gif" alt="x"/>
                                    $Top.WishListAjaxIndicator
                                    </a> --%>

                                <a class="ajax btn btn__icon" href="$Buyable.WishListRemoveLink" title="<%t Pixelpoems\Wishlist\Models\WishList.REMOVELINK "Remove from Wish List" %>">
                                    <svg aria-hidden="true" aria-labelledby="close-btn" focusable="false">
                                                <title id="close-btn">close</title>
                                                <use xlink:href="$themedResourceURL('/dist/iconset/iconset.svg')#close"></use>
                                    </svg>
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
