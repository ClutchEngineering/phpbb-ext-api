<?php
namespace clutchengineering\api\cron\task;

class cleanup_tokens extends \phpbb\cron\task\base
{
    protected $token_cleanup;
    protected $config;

    public function __construct(\clutchengineering\api\auth\token_cleanup $token_cleanup, \phpbb\config\config $config)
    {
        $this->token_cleanup = $token_cleanup;
        $this->config = $config;
    }

    public function run()
    {
        $this->token_cleanup->cleanup_expired_tokens();

        $this->config->set('clutcheng_api_cleanup_tokens_last_gc', time());
    }

    public function should_run()
    {
        return !$this->config['clutcheng_api_cleanup_tokens_last_gc'] ||
            $this->config['clutcheng_api_cleanup_tokens_last_gc'] < time() - $this->config['clutcheng_api_cleanup_tokens_gc'];
    }
}