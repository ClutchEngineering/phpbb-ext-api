<?php

/**
 * @package API
 * @copyright (c) 2024 Clutch Engineering
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace clutchengineering\api\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\user;
use phpbb\db\driver\driver_interface;
use clutchengineering\api\auth\api_auth_service;
use clutchengineering\api\auth\token_manager;

class oauth
{
    protected $auth;
    protected $user;
    protected $db;
    protected $config;
    protected $token_manager;
    protected $auth_service;

    public function __construct(
        auth $auth,
        user $user,
        driver_interface $db,
        config $config,
        token_manager $token_manager,
        api_auth_service $auth_service
    ) {
        $this->auth = $auth;
        $this->user = $user;
        $this->db = $db;
        $this->config = $config;
        $this->token_manager = $token_manager;
        $this->auth_service = $auth_service;
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
            $auth_code = $this->generate_auth_code($this->user->data['user_id']);
            $redirect_uri = $this->config['clutcheng_api_oauth_redirect_uri'];

            if (empty($redirect_uri)) {
                return new JsonResponse(['error' => 'Redirect URI not configured'], 500);
            }

            $redirect_url = $redirect_uri . (parse_url($redirect_uri, PHP_URL_QUERY) ? '&' : '?') . 'code=' . $auth_code;
            return new RedirectResponse($redirect_url);
        }
        return new JsonResponse([
            'error' => 'Invalid credentials',
            'error_code' => 2
        ], 401);
    }

    protected function generate_auth_code($user_id)
    {
        $auth_code = bin2hex(random_bytes(16)); // Generate a random 32-character string

        // Store the auth code in the database
        $sql_ary = [
            'user_id' => $user_id,
            'auth_code' => $auth_code,
            'created_at' => time(),
            'expires_at' => time() + 600, // Auth code expires in 10 minutes
        ];

        $sql = 'INSERT INTO ' . $this->token_manager->get_auth_codes_table() . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
        $this->db->sql_query($sql);

        return $auth_code;
    }

    public function token(Request $request)
    {
        $grant_type = $request->get('grant_type');
        if ($grant_type == 'authorization_code') {
            return $this->exchange_auth_code($request);
        } elseif ($grant_type == 'refresh_token') {
            return $this->refresh_token($request);
        } else {
            return new JsonResponse([
                'error' => 'Invalid grant type',
                'error_code' => 8
            ], 400);
        }
    }

    private function exchange_auth_code(Request $request) {
        $expected_redirect_uri = $this->config['clutcheng_api_oauth_redirect_uri'];
        if (empty($expected_redirect_uri)) {
            return new JsonResponse(['error' => 'Redirect URI not configured'], 500);
        }
        if ($request->get('redirect_uri') != $expected_redirect_uri) {
            return new JsonResponse([
                'error' => 'Invalid redirect URI',
                'error_code' => 7
            ], 400);
        }

        $auth_code = $request->get('code');

        if (!$auth_code) {
            return new JsonResponse([
                'error' => 'Missing authorization code',
                'error_code' => 3
            ], 400);
        }

        // Verify the auth code
        $sql = 'SELECT * FROM ' . $this->token_manager->get_auth_codes_table() . '
                WHERE auth_code = "' . $this->db->sql_escape($auth_code) . '"
                AND expires_at > ' . time();
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$row) {
            return new JsonResponse([
                'error' => 'Invalid or expired authorization code',
                'error_code' => 4
            ], 401);
        }

        // Generate tokens
        $tokens = $this->token_manager->create_tokens($row['user_id']);

        // Delete the used auth code and any expired auth codes
        $sql = 'DELETE FROM ' . $this->token_manager->get_auth_codes_table() . '
                WHERE auth_code = "' . $this->db->sql_escape($auth_code) . '" OR expires_at < ' . time();
        $this->db->sql_query($sql);

        return new JsonResponse($tokens, 200);
    }

    private function refresh_token(Request $request)
    {
        $auth_result = $this->auth_service->authenticate();
        if ($auth_result !== true) {
            return $auth_result; // This will be a JsonResponse with an error
        }
        $refresh_token = $request->get('refresh_token');
        if (!$refresh_token) {
            return new JsonResponse([
                'error' => 'Missing refresh token',
                'error_code' => 5
            ], 400);
        }

        $token = $this->auth_service->get_request_token();
        $new_tokens = $this->token_manager->refresh_token($token, $refresh_token);
        if ($new_tokens) {
            return new JsonResponse($new_tokens, 200);
        }
        return new JsonResponse([
            'error' => 'Invalid or expired refresh token',
            'error_code' => 6
        ], 401);
    }

    public function revoke_token(Request $request)
    {
        $auth_result = $this->auth_service->authenticate();
        if ($auth_result !== true) {
            return $auth_result; // This will be a JsonResponse with an error
        }

        $token = $this->auth_service->get_request_token();
        $this->token_manager->revoke_token($token);
        return new JsonResponse(['message' => 'Token revoked successfully'], 200);
    }
}