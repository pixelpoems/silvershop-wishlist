document.addEventListener('DOMContentLoaded', () => {
    init();
});

function init() {
    const wishlistButtons = document.querySelectorAll('.action-wishlist');
    wishlistButtons.forEach(button => {
        initWishlistButton(button);
    });

    const wishlistItemContainer = document.querySelector('#WishListItems');
    if (!wishlistItemContainer) return;

    const wishlistItemForms = wishlistItemContainer.querySelectorAll('form.addproductform');

    wishlistItemForms.forEach(form => {
        const variationSelect = form.querySelector('select[id^="AddProductVariationForm_VariationForm_ProductAttributes_"]');
        if (!variationSelect) return;

        const variationOptionsInput = form.querySelector('#AddProductVariationForm_VariationForm_VariationOptions');
        if (!variationOptionsInput) return;

        // Parse variation options and set the correct variation ID
        const formId = form.id;
        const variationOptions = JSON.parse(variationOptionsInput.value);
        const variationId = Object.values(variationOptions[formId])[0];

        if (variationId) {
            variationSelect.value = variationId;
        }
    });
}

function initWishlistButton(button) {
    let productID = button.dataset.productId;
    if(!productID) return;

    // We want to replace the nav item and the product actions
    let divsToReplace = ['#wishlist-menu-item', '.product--actions-' + productID];

    button.addEventListener('click', (e) => {
        e.preventDefault();

        // Hide button and show loading
        button.style.display = 'none';
        let loading = createLoadingNode();
        button.insertAdjacentElement('afterend', loading);

        // Fetch the wishlist page
        fetch(button.getAttribute('href'), {
            method: 'GET',
        }).then(async response => {
            return response.text();
        }).then(html => {

            // Replace the wishlist relevant divs
            handleReplacement(divsToReplace, html);

            // Update the wishlist link classes
            handleClassUpdates(productID, button.classList.contains('ajax--remove-from-wishlist-link'));

            // Display button and remove loading
            button.style.display = 'unset';
            loading.remove();

        }).catch((e) => {
            // console.error(e);
            console.error('Something went wrong!');

            // Display button and remove loading
            button.style.display = 'unset';
            loading.remove();

        });
    });
}

function createLoadingNode() {
    const loading = document.createElement('div');
    loading.classList.add('loading-ring');
    loading.innerHTML = '<div></div><div></div><div></div>';
    return loading;
}

function handleReplacement(divsToReplace, html) {
    const parser = new DOMParser();
    let doc = parser.parseFromString(html, 'text/html');

    // Replace the wishlist relevant divs
    divsToReplace.forEach(divToReplaceID => {

        // We use querySelectorAll because we want to replace multiple divs (e.g. the product actions of
        // product page and of the multiple loop)
        let toReplace = document.querySelectorAll(divToReplaceID);
        let replaceWith = doc.querySelectorAll(divToReplaceID);

        if(toReplace && replaceWith) {
            for(let i = 0; i < toReplace.length; i++) {
                toReplace[i].outerHTML = replaceWith[i].outerHTML;
            }
        }
    });
}

export function handleClassUpdates(productID, currentlyOnWishlist) {
    let elements = document.querySelectorAll('.action-wishlist--'+ productID);

    elements.forEach(element => {

        // Update the href based on the new class
        if(currentlyOnWishlist) {
            // New state is ADD to wishlist (item was just removed)
            element.classList.remove('ajax--remove-from-wishlist-link');
            element.classList.add('ajax--add-to-wishlist-link');
            element.href = element.dataset.addHref;
            handleDescriptionUpdate(element, element.dataset.addDescription);
        } else {
            // New state is REMOVE from wishlist (item was just added)
            element.classList.add('ajax--remove-from-wishlist-link');
            element.classList.remove('ajax--add-to-wishlist-link');
            element.href = element.dataset.removeHref;
            handleDescriptionUpdate(element, element.dataset.removeDescription);
        }
    });
}

function handleDescriptionUpdate(element, description) {
    let elDescription = element.querySelector('.action-wishlist__description');
    if(!elDescription) return;
    elDescription.innerText = description;
}
