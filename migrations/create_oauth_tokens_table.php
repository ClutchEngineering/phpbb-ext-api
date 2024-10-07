<?php

namespace clutchengineering\api\migrations;

class create_oauth_tokens_table extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'clutcheng_api_oauth_tokens');
    }

    public static function depends_on()
    {
        return ['\clutchengineering\api\migrations\add_jwt_secret_key'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'clutcheng_api_oauth_tokens' => [
                    'COLUMNS' => [
                        'token_id'      => ['UINT', null, 'auto_increment'],
                        'user_id'       => ['UINT', 0],
                        'access_token'  => ['VCHAR:255', ''],
                        'refresh_token' => ['VCHAR:255', ''],
                        'created_at'    => ['TIMESTAMP', 0],
                        'expires_at'    => ['TIMESTAMP', 0],
                    ],
                    'PRIMARY_KEY' => 'token_id',
                    'KEYS' => [
                        'user_id' => ['INDEX', 'user_id'],
                    ],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'clutcheng_api_oauth_tokens',
            ],
        ];
    }
}