<?php

namespace clutchengineering\api\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use clutchengineering\api\middleware\RateLimiter;

class ApiRateLimitListener implements EventSubscriberInterface
{
    private $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1],
        ];
    }

    public function onKernelRequest($event)
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Check if the request is for an API route
        if (strpos($path, '/api/') === 0) {
            $response = $this->rateLimiter->__invoke($request, function ($request) {
                return new Response();
            });

            if ($response->getStatusCode() === 429) {
                if (method_exists($event, 'setResponse')) {
                    $event->setResponse($response);
                } else {
                    $event->setResponseRaw($response);
                }
            } else {
                // Store rate limit headers to be added later
                $request->attributes->set('rate_limit_headers', $response->headers->all());
            }
        }
    }
}