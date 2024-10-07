<?php

namespace clutchengineering\api\migrations;

class add_oauth_redirect_uri_config extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['clutcheng_api_oauth_redirect_uri']);
    }

    static public function depends_on()
    {
        return ['\clutchengineering\api\migrations\add_jwt_secret_key'];
    }

    public function update_data()
    {
        return [
            ['config.add', ['clutcheng_api_oauth_redirect_uri', '']],
        ];
    }
}