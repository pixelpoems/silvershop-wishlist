<% if $CurrentUser %>
    <ul id="product--actions-{$ProductID} <% if $Variation %>variation--actions-{$ID}<% end_if %>"
        class="wishlist productActions <% if $IsInCart %>inCart<% else %>notInCart<% end_if %> <% if $IsInWishList %>inWishList<% else %>notInWishList<% end_if %> product--actions-{$ProductID} <% if $Variation %>variation--actions-{$ID}<% end_if %>"
    >
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
                <% include Pixelpoems\Wishlist\Includes\WishList_Icon %>
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
            <a href="{$LoginURL}?BackURL={$Link}"
               class="btn btn__icon btn--link action-wishlist action-wishlist--{$ID}">
                <% include Pixelpoems\Wishlist\Includes\WishList_Icon %>
                <% if $Size != 'sm' %>
                    <span class="action-wishlist__description">
                        <%t Pixelpoems\Wishlist\Models\WishList.ADDLINK "Add to Wish List" %>
                    </span>
                <% end_if %>
            </a>
        </li>
    </ul>
<% end_if %>

