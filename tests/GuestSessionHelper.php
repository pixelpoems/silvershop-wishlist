<?php

declare(strict_types=1);

namespace Pixelpoems\Wishlist\Tests;

use Pixelpoems\Wishlist\Models\WishList;
use ReflectionProperty;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;

/**
 * WishList::current()'s guest (not-logged-in) path reads/writes a token via
 * Controller::curr()->getRequest()->getSession() - there is no such thing in
 * a plain SapphireTest (only FunctionalTest requests have one). This pushes
 * a Controller with a real Session onto the controller stack so that path
 * has something to read/write, and pops it again afterwards.
 */
trait GuestSessionHelper
{
    private ?Controller $guestSessionController = null;

    protected function startGuestSession(array $data = []): Session
    {
        $session = new Session($data);
        $request = new HTTPRequest('GET', '/');
        $request->setSession($session);

        $this->guestSessionController = new Controller();
        $this->guestSessionController->setRequest($request);
        $this->guestSessionController->pushCurrent();

        return $session;
    }

    protected function stopGuestSession(): void
    {
        if ($this->guestSessionController) {
            $this->guestSessionController->popCurrent();
            $this->guestSessionController = null;
        }
    }

    /**
     * WishList::current() caches its result in a plain static property that
     * SapphireTest does not reset between calls - clear it to simulate a
     * fresh request while keeping the same guest session (and its token)
     * alive.
     */
    protected function resetCurrentListCache(): void
    {
        $property = new ReflectionProperty(WishList::class, 'current');
        $property->setValue(null, null);
    }
}