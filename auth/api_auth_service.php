<?php

namespace clutchengineering\api\auth;

use Symfony\Component\HttpFoundation\JsonResponse;
use clutchengineering\api\auth\token_manager;

class api_auth_service
{
    protected $token_manager;

    public function __construct(token_manager $token_manager)
    {
        $this->token_manager = $token_manager;
    }

    public function get_request_token() {
        // Note that apache mod_php 'eats' the Authorization header, so we need to use apache_request_headers() instead
        // https://stackoverflow.com/questions/19443718/symfony-2-3-getrequest-headers-not-showing-authorization-bearer-token
        $token = apache_request_headers()['Authorization'];

        // If $token doesn't have 'Bearer ' at the beginning, it's invalid
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            return NULL;
        }

        return str_replace('Bearer ', '', $token);
    }

    public function authenticate()
    {
        $token = $this->get_request_token();

        // If $token doesn't have 'Bearer ' at the beginning, it's invalid
        if ($token === NULL || !$token) {
            return new JsonResponse(['error' => 'No token provided'], 401);
        }

        $token = str_replace('Bearer ', '', $token);

        if (!$this->token_manager->validate_token($token)) {
            return new JsonResponse(['error' => 'Invalid or expired token'], 401);
        }

        return true;
    }
}