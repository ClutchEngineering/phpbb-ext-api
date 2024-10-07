<?php

namespace clutchengineering\api\migrations;

class add_jwt_secret_key extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['clutcheng_api_jwt_secret_key']) && isset($this->config['clutcheng_api_jwt_secret_key_set']);
    }

    public function update_data()
    {
        return [
            ['config.add', ['clutcheng_api_jwt_secret_key', '']],
            ['config.add', ['clutcheng_api_jwt_secret_key_set', '0', true]],
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_API_SETTINGS'
            ]],
            ['module.add', [
                'acp',
                'ACP_API_SETTINGS',
                [
                    'module_basename'	=> '\clutchengineering\api\acp\main_module',
                    'modes'				=> ['settings'],
                ],
            ]],
        ];
    }
}