<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExpiredSessionRedirectSubscriber implements EventSubscriberInterface
{
    private const PROTECTED_PREFIXES = ['/api', '/visualisation', '/families', '/trees'];

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['redirectProtectedLoginToHome', -10]];
    }

    public function redirectProtectedLoginToHome(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (!array_any(self::PROTECTED_PREFIXES, static fn (string $prefix): bool => str_starts_with($path, $prefix))) {
            return;
        }

        $response = $event->getResponse();
        if (!$response->isRedirection()) {
            return;
        }

        $target = $response->headers->get('Location', '');
        if (!str_ends_with(parse_url($target, PHP_URL_PATH) ?: '', '/login')) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_home')));
    }
}
