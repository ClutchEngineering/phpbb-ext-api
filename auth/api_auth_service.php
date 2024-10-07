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

    public function authenticate($token)
    {
        if (!$token) {
            return new JsonResponse(['error' => 'No token provided'], 401);
        }

        $token = str_replace('Bearer ', '', $token);

        if (!$this->token_manager->validate_token($token)) {
            return new JsonResponse(['error' => 'Invalid or expired token'], 401);
        }

        return true;
    }
}