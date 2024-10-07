<?php

/**
 * @package API
 * @copyright (c) 2024 Clutch Engineering
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace clutchengineering\api\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\user;
use phpbb\db\driver\driver_interface;
use clutchengineering\api\auth\token_manager;

class oauth
{
    protected $auth;
    protected $user;
    protected $db;
    protected $config;
    protected $token_manager;

    public function __construct(auth $auth, user $user, driver_interface $db, config $config, token_manager $token_manager)
    {
        $this->auth = $auth;
        $this->user = $user;
        $this->db = $db;
        $this->config = $config;
        $this->token_manager = $token_manager;
    }

    public function login(Request $request)
    {
        if (!$this->config['clutcheng_api_jwt_secret_key'] || $this->config['clutcheng_api_jwt_secret_key_set'] !== '1') {
            return new JsonResponse([
                'error' => 'OAuth not properly configured for this server.',
                'error_code' => 1
            ], 500);
        }

        $username = $request->get('username');
        $password = $request->get('password');

        if (!$username || !$password) {
            return new JsonResponse(['error' => 'Missing credentials'], 400);
        }

        $result = $this->auth->login($username, $password);
        if ($result['status'] == LOGIN_SUCCESS)
        {
            $tokens = $this->token_manager->create_tokens($this->user->data['user_id']);
            return new JsonResponse($tokens, 200);
        }
        return new JsonResponse(['error' => 'Invalid credentials'], 401);
    }

    public function refresh_token(Request $request)
    {
        $refresh_token = $request->get('refresh_token');

        if (!$refresh_token) {
            return new JsonResponse(['error' => 'Missing refresh token'], 400);
        }

        $new_tokens = $this->token_manager->refresh_token($refresh_token);
        if ($new_tokens) {
            return new JsonResponse($new_tokens, 200);
        }
        return new JsonResponse(['error' => 'Invalid or expired refresh token'], 401);
    }

    public function revoke_token(Request $request)
    {
        $token = $request->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Missing token'], 400);
        }

        $this->token_manager->revoke_token($token);
        return new JsonResponse(['message' => 'Token revoked successfully'], 200);
    }
}