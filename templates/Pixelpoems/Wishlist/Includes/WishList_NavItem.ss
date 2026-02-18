<% if $WishListPageLink %>
    <li class="menu-item" id="wishlist-menu-item">
        <a href="$WishListPageLink" class="btn btn__icon" aria-label="Whishlist">
            <svg aria-hidden="true" aria-labelledby="heart-btn" focusable="false">
                <title id="heart-btn">heart</title>
                <use xlink:href="$themedResourceURL('/dist/iconset/iconset.svg')#heart"></use>
            </svg>
        </a>
        <% if $WishListItemCount >= 1 %>
            <span class="item-qty">$WishListItemCount</span>
        <% end_if %>
    </li>
<% end_if %>
