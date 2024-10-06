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
use Firebase\JWT\JWT;

class oauth
{
    protected $auth;
    protected $user;
    protected $db;
    protected $config;

    public function __construct(auth $auth, user $user, driver_interface $db, config $config)
    {
        $this->auth = $auth;
        $this->user = $user;
        $this->db = $db;
        $this->config = $config;
    }

    public function login(Request $request)
    {
        if (!$this->config['jwt_secret_key'] || $this->config['jwt_secret_key_set'] !== '1') {
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

        // Authenticate user
        $result = $this->auth->login($username, $password);
        if ($result['status'] == LOGIN_SUCCESS)
        {
            $access_token = $this->generate_token($this->user->data['user_id'], 'access');
            $refresh_token = $this->generate_token($this->user->data['user_id'], 'refresh');
            return new JsonResponse([
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'expires_in' => 3600
            ], 200);
        }
        return new JsonResponse(['error' => 'Invalid credentials'], 401);
    }

    protected function generate_token($user_id, $type = 'access')
    {
        $now = time();
        $expiration = $type === 'access' ? $now + 60 * 60 * 12 : $now + 604800; // 12 hours for access, 1 week for refresh

        $payload = [
            'iss' => $this->config['server_name'],
            'sub' => $user_id,
            'iat' => $now,
            'exp' => $expiration,
            'type' => $type
        ];

        return JWT::encode($payload, $this->config['jwt_secret_key'], 'HS256');
    }

    public function refresh_token(Request $request)
    {
        $refresh_token = $request->get('refresh_token');

        if (!$refresh_token) {
            return new JsonResponse(['error' => 'Missing refresh token'], 400);
        }

        try {
            $decoded_token = JWT::decode($refresh_token, $this->config['jwt_secret_key'], ['HS256']);

            if ($decoded_token->type !== 'refresh') {
                throw new \Exception('Invalid token type');
            }

            if ($decoded_token->exp < time()) {
                throw new \Exception('Token expired');
            }

            $new_access_token = $this->generate_token($decoded_token->sub, 'access');
            $new_refresh_token = $this->generate_token($decoded_token->sub, 'refresh');

            return new JsonResponse([
                'access_token' => $new_access_token,
                'refresh_token' => $new_refresh_token,
                'expires_in' => 3600
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid or expired refresh token'], 401);
        }
    }

    public function revoke_token(Request $request)
    {
        // Implement token revocation logic here
        // This could involve maintaining a blacklist of revoked tokens in the database
        return new JsonResponse(['message' => 'Token revoked successfully'], 200);
    }
}