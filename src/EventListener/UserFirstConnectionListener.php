<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Request;

class FirstConnectionListener implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(TokenStorageInterface $tokenStorage, UrlGeneratorInterface $urlGenerator)
    {
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Check if the user is authenticated
        if (!$this->tokenStorage->getToken()) {
            return;
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        // Check if it's the first connection and redirect if true
        if ($user->isFirstConnection() && !$this->isOnNewPasswordPage($event->getRequest())) {
            $url = $this->urlGenerator->generate('app_change_password');
            $response = new RedirectResponse($url);
            $event->setResponse($response);
        }
    }

    private function isOnNewPasswordPage(Request $request): bool
    {
        // Check if the current request is for the new password page
        $currentRoute = $request->attributes->get('_route');

        return $currentRoute === 'app_change_password';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
