# Silvershop Wishlist

A [Silverstripe](https://www.silverstripe.org/) module that adds a single wishlist per member to a [Silvershop](https://github.com/silvershop/silvershop-core) storefront. Logged-in customers can add products (and product variations) to their wishlist, view it on a dedicated page, and remove items again — with an AJAX-driven "add/remove" button that updates in place without a full page reload.

## Requirements

- PHP ^8.3 || ^8.4 || ^8.5
- `silverstripe/cms` ^6.0
- `silvershop/core` ^6

## Installation

```bash
composer require pixelpoems/silvershop-wishlist
```

Then run a `dev/build` to create the database tables and the `WishListPage`, which is created automatically on first build (see [Auto-created page](#auto-created-page) below).

### Frontend assets

The module ships pre-built CSS/JS in `client/dist/`, which is exposed as a public resource via composer's `expose` config, so no build step is required to use the module as-is.

If you want to modify the frontend source (`client/src/`):

```bash
yarn install
yarn build
```

This runs Webpack and regenerates `client/dist/javascript/wishlist.min.js` and `client/dist/css/wishlist.min.css`.

## What it does

Every logged-in member has one wishlist, created automatically the first time they add something to it. Products and variations get an add/remove link (via an extension applied to Silvershop's `Product` and `Variation` classes), which posts to a dedicated `WishListPage` that lists, adds and removes items. A small JS enhancement intercepts clicks on add/remove buttons and updates the page (e.g. a nav badge showing the item count) in place, without a full reload.

### Auto-created page

`WishListPage::requireDefaultRecords()` creates a single `WishListPage` (hidden from menus and search, restricted to logged-in users) on `dev/build` if one doesn't already exist. Disable this by setting `auto_create_page: false` via YAML config:

```yaml
Pixelpoems\Wishlist\Pages\WishListPage:
  auto_create_page: false
```
## CSS and JS Coming along
This module comes with basic CSS and JS to make the wishlist button and nav badge look nice, but you will likely want to customize it for your theme. The module's CSS/JS is loaded automatically via Silverstripe's `Requirements` API, but you can disable that and load your own files instead:

```yaml
Pixelpoems\Wishlist\Pages\WishListPage:
  load_default_css: false
  load_default_js: false
```
The Wishlist logic is also working without js - so then the add/remove button will reload the page to update the nav badge and button state. The JS is just a progressive enhancement to avoid full page reloads.

## Usage in templates

Render the wishlist nav badge (e.g. in your header):

```silverstripe
<% include Pixelpoems\Wishlist\Includes\WishList_NavItem %>
```

Render an add/remove button on a product/variation (see `CanAddToWishListExtension`):

```silverstripe
<% include Pixelpoems\Wishlist\Includes\Product_WishListAction ProductID=$ID %>
```

`ProductID` should always be the *product's* ID (not the variation's) - pass `$Up.ID` when looping over `$Variations`, and add `Variation=true` in that case so the markup gets a `variation--actions-{$ID}` id/class alongside the shared `product--actions-{$ProductID}` one. Pass `Size=sm` to render an icon-only button (no text label), which is the default for anything but `sm`.

The bundled `wishlist.js` progressively enhances any `.action-wishlist` button: it intercepts the click, fetches the add/remove link via AJAX, replaces the button's `product--actions-{$ProductID}` container (and the nav badge) with the freshly rendered HTML from the response, and toggles the button's add/remove state without a full page reload.

### Customizing the icon

The heart icon markup lives in its own include, `Pixelpoems\Wishlist\Includes\WishList_Icon`, which `Product_WishListAction.ss` includes and switches on `$IsInWishList`. To use your own icon (e.g. a sprite `<use>` reference instead of the bundled inline SVGs), override just this one file rather than the whole button - create a template at the same relative path in your project/theme:

```
app/templates/Pixelpoems/Wishlist/Includes/WishList_Icon.ss
```

Because the ajax response re-renders the button (and therefore this include) server-side on every add/remove, an overridden icon stays correct after a click too - there's no icon markup duplicated in `wishlist.js` to keep in sync.

## Translations

Bundled `lang/` files: `en`, `de`, `es`, `fr`, `pl`, `ro`.

## Known limitations

This module is a work in progress:

- Only a **single wishlist per member** is currently supported. Multi-list support (create/delete/switch lists, a title-editing form) is scaffolded in `WishList`, `WishListPageController` and `WishListPage` but commented out / not wired up yet.

## Reporting Issues

Please [create an issue](https://github.com/pixelpoems/silvershop-wishlist/issues) for any bugs you've found, or
features you're missing.

## Credits

Icons from Font Awesome - https://fontawesome.com
