<?php
// migrations/create_auth_codes_table.php
namespace clutchengineering\api\migrations;

class create_auth_codes_table extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'clutcheng_api_oauth_auth_codes');
    }

    static public function depends_on()
    {
        return ['\clutchengineering\api\migrations\create_oauth_tokens_table'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'clutcheng_api_oauth_auth_codes' => [
                    'COLUMNS' => [
                        'auth_code_id' => ['UINT', null, 'auto_increment'],
                        'user_id'      => ['UINT', 0],
                        'auth_code'    => ['VCHAR:255', ''],
                        'created_at'   => ['TIMESTAMP', 0],
                        'expires_at'   => ['TIMESTAMP', 0],
                    ],
                    'PRIMARY_KEY' => 'auth_code_id',
                    'KEYS' => [
                        'auth_code' => ['UNIQUE', 'auth_code'],
                    ],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'clutcheng_api_oauth_auth_codes',
            ],
        ];
    }
}