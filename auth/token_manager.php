<?php

namespace clutchengineering\api\auth;

use phpbb\db\driver\driver_interface;
use phpbb\config\config;
use Firebase\JWT\JWT;

class token_manager
{
    protected $db;
    protected $config;
    protected $tokens_table;
    protected $auth_codes_table;

    public function __construct(driver_interface $db, config $config, $table_prefix)
    {
        $this->db = $db;
        $this->config = $config;
        $this->tokens_table = $table_prefix . 'clutcheng_api_oauth_tokens';
        $this->auth_codes_table = $table_prefix . 'clutcheng_api_oauth_auth_codes';
    }

    public function get_auth_codes_table()
    {
        return $this->auth_codes_table;
    }

    public function create_tokens($user_id)
    {
        $access_token = $this->generate_token($user_id, 'access');
        $refresh_token = $this->generate_token($user_id, 'refresh');

        $this->store_tokens($user_id, $access_token, $refresh_token);

        return [
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'expires_in' => 3600 // 1 hour
        ];
    }

    protected function generate_token($user_id, $type = 'access')
    {
        $now = time();
        $expiration = $type === 'access' ? $now + 3600 : $now + 604800; // 1 hour for access, 1 week for refresh

        $payload = [
            'iss' => $this->config['server_name'],
            'sub' => $user_id,
            'iat' => $now,
            'exp' => $expiration,
            'type' => $type
        ];

        return JWT::encode($payload, $this->config['clutcheng_api_jwt_secret_key'], 'HS256');
    }

    protected function store_tokens($user_id, $access_token, $refresh_token)
    {
        $sql_ary = [
            'user_id' => $user_id,
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'created_at' => time(),
            'expires_at' => time() + 3600 // 1 hour
        ];

        $sql = 'INSERT INTO ' . $this->tokens_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
        $this->db->sql_query($sql);
    }

    public function validate_token($token)
    {
        try {
            $decoded = JWT::decode($token, $this->config['clutcheng_api_jwt_secret_key'], ['HS256']);
            
            // Check if token exists in database and is not expired
            $sql = 'SELECT * FROM ' . $this->tokens_table . ' 
                    WHERE access_token = ' . $this->db->sql_escape($token) . '
                    AND expires_at > ' . time();
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            return $row ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function refresh_token($refresh_token)
    {
        try {
            $decoded = JWT::decode($refresh_token, $this->config['clutcheng_api_jwt_secret_key'], ['HS256']);
            
            if ($decoded->type !== 'refresh') {
                throw new \Exception('Invalid token type');
            }

            $sql = 'SELECT * FROM ' . $this->tokens_table . ' 
                    WHERE refresh_token = ' . $this->db->sql_escape($refresh_token);
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if (!$row) {
                throw new \Exception('Refresh token not found');
            }

            // Generate new tokens
            $new_tokens = $this->create_tokens($decoded->sub);

            // Update database
            $sql_ary = [
                'access_token' => $new_tokens['access_token'],
                'refresh_token' => $new_tokens['refresh_token'],
                'created_at' => time(),
                'expires_at' => time() + 3600
            ];

            $sql = 'UPDATE ' . $this->tokens_table . ' 
                    SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
                    WHERE refresh_token = ' . $this->db->sql_escape($refresh_token);
            $this->db->sql_query($sql);

            return $new_tokens;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function revoke_token($token)
    {
        $sql = 'DELETE FROM ' . $this->tokens_table . ' 
                WHERE access_token = ' . $this->db->sql_escape($token) . '
                OR refresh_token = ' . $this->db->sql_escape($token);
        $this->db->sql_query($sql);
    }
}