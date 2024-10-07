<?php

namespace clutchengineering\api\middleware;

use Symfony\Component\HttpFoundation\JsonResponse;
use phpbb\cache\driver\driver_interface as cache_driver;

class RateLimiter
{
    private $requestsPerMinute;
    private $cache;

    public function __construct($requestsPerMinute = 60, cache_driver $cache)
    {
        $this->requestsPerMinute = $requestsPerMinute;
        $this->cache = $cache;
    }

    public function __invoke($request, callable $next)
    {
        $clientIp = $request->getClientIp();
        $cacheKey = 'rate_limit_' . md5($clientIp);
        $currentTime = time();

        $rateData = $this->cache->get($cacheKey);
        if ($rateData === false) {
            $rateData = [
                'requests' => 0,
                'reset_time' => $currentTime + 60
            ];
        }

        if ($currentTime > $rateData['reset_time']) {
            $rateData = [
                'requests' => 0,
                'reset_time' => $currentTime + 60
            ];
        }

        $rateData['requests']++;

        $remainingRequests = max(0, $this->requestsPerMinute - $rateData['requests']);
        $resetTime = $rateData['reset_time'];

        // Store the updated rate data
        $this->cache->put($cacheKey, $rateData, 60);

        if ($remainingRequests === 0) {
            $response = new JsonResponse([
                'error' => 'Rate limit exceeded',
            ], 429);
        } else {
            $response = $next($request);
        }

        // Add rate limit headers to the response
        $response->headers->set('X-RateLimit-Limit', $this->requestsPerMinute);
        $response->headers->set('X-RateLimit-Remaining', $remainingRequests);
        $response->headers->set('X-RateLimit-Reset', $resetTime);

        return $response;
    }
}