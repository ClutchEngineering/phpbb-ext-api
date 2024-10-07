<?php
namespace clutchengineering\api\auth;

use phpbb\db\driver\driver_interface;
use phpbb\config\config;

class token_cleanup
{
    protected $db;
    protected $config;
    protected $tokens_table;

    public function __construct(driver_interface $db, config $config, $table_prefix)
    {
        $this->db = $db;
        $this->config = $config;
        $this->tokens_table = $table_prefix . 'clutcheng_api_oauth_tokens';
    }

    public function cleanup_expired_tokens()
    {
        $sql = 'DELETE FROM ' . $this->tokens_table . '
                WHERE expires_at < ' . time();
        $this->db->sql_query($sql);

        $affected_rows = $this->db->sql_affectedrows();

        if ($affected_rows > 0) {
            add_log('admin', 'LOG_OAUTH_TOKENS_CLEANED', $affected_rows);
        }
    }
}