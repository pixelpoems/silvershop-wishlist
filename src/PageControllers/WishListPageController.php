<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Controllers;

use PageController;
use Pixelpoems\Wishlist\Models\WishList;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

class WishListPageController extends PageController
{
    private static array $allowed_actions = [
        'add', 'remove', 'remove_all',
        'delete_list', 'create_list',
        'set_current_list',
        'WishListForm',
    ];

    protected WishList $wishList;

    /**
     * Initialize the controller
     * @throws HTTPResponse_Exception
     */
    protected function init()
    {
        parent::init();
        if (!Security::getCurrentUser()) {
            return $this->httpError(404);
        }

        $this->wishList = WishList::current();
//        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
//        Requirements::javascript(ECOMMERCE_WISHLIST_FOLDER . '/javascript/WishListPage.js');
    }

    /**
     * @throws HTTPResponse_Exception
     */
    private function getItemFromRequest(HTTPRequest $request): DataObject
    {
        // check out the inputs
        $id = (int)$request->param('ID');
        $sanitisedClassname = $request->param('OtherID');

        if (!$id || !$sanitisedClassname) {
            $this->httpError(400);
        }

         // bad request
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->httpError(403);
        }

        $className = str_replace('-', '\\', $sanitisedClassname);

        // look up the item
        $item = DataObject::get($className)->byID($id);
        if (!$item || !$item->exists()) {
            $this->httpError(404);
        }

        return $item;
    }

    /**
     * @throws HTTPResponse_Exception
     */
    public function add(HTTPRequest $request): HTTPResponse
    {
        $item = $this->getItemFromRequest($request);

        // add it to the list
        $list = WishList::current();
        $list->addBuyable($item);

        return $this->redirectBack();
    }

    /**
     * @throws HTTPResponse_Exception
     */
    public function remove(HTTPRequest $request): HTTPResponse
    {
        $item = $this->getItemFromRequest($request);

        // remove it from the list
        $list = WishList::current();
        $list->removeBuyable($item);

        return $this->redirectBack();
    }

    /**
     * @throws HTTPResponse_Exception
     */
    public function remove_all(HTTPRequest $request): HTTPResponse
    {
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->httpError(403);
        }

        $this->wishList->removeAllBuyables();
        return $this->redirectBack();
    }

//    public function delete_list(HTTPRequest $request)
//    {
//        // ToDo: Implement delete_list() method.
//    }
//
//    public function create_list(HTTPRequest $request)
//    {
//        // ToDo: Implement create_list() method.
//    }
//
//    public function set_current_list(HTTPRequest $request)
//    {
//        // ToDo: Implement set_current_list() method.
//    }

//    public function WishListForm(): Form
//    {
//        return Form::create(
//            $this,
//            'WishListForm',
//            // Fields:
//            FieldList::create([
//                TextField::create('Title', $this->wishList->Title)
//                    ->setAttribute('placeholder', 'Name for List')
//            ]),
//            // Actions
//            FieldList::create([
//                FormAction::create('saveList', 'Save'),
//                FormAction::create('cancelEdit', 'Cancel')
//            ]),
//            // Required
//            RequiredFields::create(['Title'])
//        );
//    }

//    public function saveList(array $data, Form $form): HTTPResponse
//    {
//        if (!isset($data['Title']) || trim($data['Title']) == '') {
//            $this->wishList->Title = 'Wish List'; // ToD: Add Translation
//        } else {
//            $this->wishList->Title = $data['Title'];
//        }
//
//        $this->wishList->write();
//        return $this->redirectBack();
//    }


    public function cancelEdit(): HTTPResponse
    {
        return $this->redirectBack();
    }

    public function CurrentList(): WishList
    {
        if (!isset($this->wishList)) {
            $this->wishList = WishList::current();
        }

        return $this->wishList;
    }

//    public function AllLists()
//    {
//        return WishList::get_for_user();
//    }

    public function RemoveAllLink()
    {
        return SecurityToken::inst()->addToUrl($this->Link('remove-all'));
    }

//    public function DeleteListLink()
//    {
//        return SecurityToken::inst()->addToUrl($this->Link('delete-list'));
//    }
//
//    public function CreateListLink()
//    {
//        return SecurityToken::inst()->addToUrl($this->Link('create-list'));
//    }
}
