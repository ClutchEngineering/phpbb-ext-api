<?php

namespace clutchengineering\api\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiResponseListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse($event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->attributes->has('rate_limit_headers')) {
            $rateLimitHeaders = $request->attributes->get('rate_limit_headers');
            foreach ($rateLimitHeaders as $name => $values) {
                $response->headers->set($name, $values);
            }
        }
    }
}