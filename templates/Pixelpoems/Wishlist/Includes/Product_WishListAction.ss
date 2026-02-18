<%--<% if $HasVariations %>--%>
<%--    <ul class="productActions <% if $VariationIsInCart %>inCart<% else %>notInCart<% end_if %>" id="$AJAXDefinitions.UniqueIdentifier">--%>
<%--        <li class="variationsLink">--%>
<%--            <a class="selectVariation btn action ajaxAddToCartLink" href="{$AddVariationsLink}"rel="VariationsTable{$ID}" title="<%t Product.UPDATECART "update cart for" %> $Title.ATT">--%>
<%--			<span class="removeLink"><%t Product.INCART "In Cart" %></span>--%>
<%--            <span class="addLink"><%t Product.ADDLINK "Add to cart" %></span>--%>
<%--                </a>--%>
<%--        </li>--%>
<%--    </ul>--%>
<%--<% else %>--%>
<%if not $isGift %>
    <% if $CurrentUser %>
        <ul id="product--actions-{$ProductID} <% if $Variation %>variation--actions-{$ID}<% end_if %>"
            class="wishlist productActions <% if $IsInCart %>inCart<% else %>notInCart<% end_if %> <% if $IsInWishList %>inWishList<% else %>notInWishList<% end_if %> product--actions-{$ProductID} <% if $Variation %>variation--actions-{$ID}<% end_if %>"
        >
<%--        <li class="removeLink">--%>
<%--            <a class="goToCartLink btn action" href="$EcomConfig.CheckoutLink" title="<%t Product.GOTOCHECKOUTLINK "Go to the checkout" %>">--%>
<%--			<span class="removeLink goToCartLink"><%t Product.GOTOCHECKOUTLINK "Go to the checkout" %></span>--%>
<%--            </a>--%>
<%--            <a class="ajaxBuyableRemove ajaxRemoveFromCartLink" href="$RemoveAllLink" title="<%t Product.REMOVELINK "Remove from Cart" %>">--%>
<%--			<span class="removeLink"><%t Product.REMOVELINK "Remove from Cart" %></span>--%>
<%--            </a>--%>
<%--        </li>--%>
<%--        <li class="addLink">--%>
<%--            <a class="ajaxBuyableAdd btn action ajaxAddToCartLink" href="$AddLink" title="<%t Product.ADDLINK "Add to Cart" %>">--%>
<%--			<span class="addLink"><%t Product.ADDLINK "Add to Cart" %></span>--%>
<%--            </a>--%>
<%--        </li>--%>
            <li>
            <a href="<% if $IsInWishList %>$WishListRemoveLink<% else %>$WishListAddLink<% end_if %>"
               data-product-id="$ID"
               data-remove-href="$WishListRemoveLink"
               data-add-href="$WishListAddLink"
               data-add-description="<%t Pixelpoems\Wishlist\Models\WishList.ADDLINK "Add to Wish List" %>"
               data-remove-description="<%t Pixelpoems\Wishlist\Models\WishList.REMOVELINK "Remove from Wish List" %>"


                <%-- ATTENTION THOSE CLASSES ARE ALL NEEDED BY JS --%>
               class="btn btn__icon btn--link action-wishlist action-wishlist--{$ID} <% if $IsInWishList %>ajax--remove-from-wishlist-link<% else %>ajax--add-to-wishlist-link<% end_if %>"
            >


                <svg aria-hidden="true" aria-labelledby="heart-btn" focusable="false" data-base-href="$themedResourceURL('/dist/iconset/iconset.svg')">
                    <title id="heart-btn-$ID"><% if $IsInWishList %><%t Pixelpoems\Wishlist\Models\WishList.REMOVELINK "Remove from Wish List" %><% else %><%t Pixelpoems\Wishlist\Models\WishList.ADDLINK "Add to Wish List" %><% end_if %></title>
                    <use href="$themedResourceURL('/dist/iconset/iconset.svg')#heart"></use>
                </svg>
                <% if $Size != 'sm' %>
                    <span class="action-wishlist__description">
                        <% if $IsInWishList %>
                            <%t Pixelpoems\Wishlist\Models\WishList.REMOVELINK "Remove from Wish List" %>
                        <% else %>
                            <%t Pixelpoems\Wishlist\Models\WishList.ADDLINK "Add to Wish List" %>
                        <% end_if %>
                    </span>
                <% end_if %>

            </a>
            </li>
    </ul>
    <% else %>
        <ul id="product--actions-{$ProductID} <% if $Variation %>variation--actions-{$ID}<% end_if %>"
            class="wishlist productActions notInCart notInWishList product--actions-{$ProductID} <% if $Variation %>variation--actions-{$ID}<% end_if %>"
        >
            <li>
            <a href="{$LoginURL}?BackURL={$Link}" class="btn btn__icon btn--link action-wishlist action-wishlist--{$ID}">
                <svg aria-hidden="true" aria-labelledby="heart-btn" focusable="false" data-base-href="$themedResourceURL('/dist/iconset/iconset.svg')">
                    <title id="heart-btn-$ID"><% if $IsInWishList %><%t Pixelpoems\Wishlist\Models\WishList.REMOVELINK "Remove from Wish List" %><% else %><%t Pixelpoems\Wishlist\Models\WishList.ADDLINK "Add to Wish List" %><% end_if %></title>
                    <use href="$themedResourceURL('/dist/iconset/iconset.svg')#heart"></use>
                </svg>
                <% if $Size != 'sm' %>
                    <span class="action-wishlist__description">
                        <%t Pixelpoems\Wishlist\Models\WishList.ADDLINK "Add to Wish List" %>
                    </span>
                <% end_if %>
            </a>
            </li>
        </ul>
    <% end_if %>
<% end_if %>

<%--<% end_if %>--%>
