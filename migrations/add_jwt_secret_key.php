<?php
// in migrations/add_jwt_secret_key.php
namespace clutchengineering\api\migrations;

class add_jwt_secret_key extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['jwt_secret_key']) && isset($this->config['jwt_secret_key_set']);
    }

    static public function depends_on()
    {
        return ['\clutchengineering\api\migrations\install'];
    }

    public function update_data()
    {
        return [
            ['config.add', ['jwt_secret_key', '']],
            ['config.add', ['jwt_secret_key_set', '0', true]],
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